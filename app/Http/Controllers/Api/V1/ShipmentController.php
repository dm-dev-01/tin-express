<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Models\CourierMapping;
use App\Services\Couriers\CourierStrategyFactory;
use App\Services\Couriers\CourierService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ShipmentController extends Controller
{
    protected $courierFactory;

    public function __construct(CourierStrategyFactory $courierFactory)
    {
        $this->courierFactory = $courierFactory;
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $query = Shipment::where('company_id', $user->company_id);

        if ($request->search) {
            $term = $request->search;
            $query->where(function ($q) use ($term) {
                $q->where('id', 'like', "%$term%")
                    ->orWhere('tracking_number', 'like', "%$term%")
                    ->orWhere('receiver_name', 'like', "%$term%")
                    ->orWhere('external_order_number', 'like', "%$term%");
            });
        }

        return response()->json($query->with('items')
            ->orderBy('created_at', 'desc')
            ->paginate(10));
    }

    public function show(Request $request, Shipment $shipment)
    {
        if ($request->user()->company_id !== $shipment->company_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        return response()->json($shipment->load(['items', 'user', 'quotes']));
    }

    public function book(Request $request, Shipment $shipment)
    {
        if ($request->user()->company_id !== $shipment->company_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($shipment->status !== 'draft') {
            return response()->json(['message' => 'Shipment is already booked'], 400);
        }

        $validated = $request->validate([
            'quote_id' => 'required|exists:shipment_quotes,id',
        ]);

        $selectedQuote = $shipment->quotes()->where('id', $validated['quote_id'])->firstOrFail();

        // 1. Resolve Strategy via DB Mapping
        $mapping = CourierMapping::where('name_in_app', $selectedQuote->courier_name)
            ->with('carrierConfig')
            ->first();

        if (!$mapping || !$mapping->carrierConfig) {
            return response()->json(['message' => 'Courier configuration not found.'], 500);
        }

        try {
            $strategy = $this->courierFactory->createStrategy($mapping->carrierConfig);

            // 2. Execute Booking
            $bookingResult = $strategy->createConsignment($shipment, $selectedQuote);

            // 3. Save Data (Including Label if strategy returned one immediately)
            $shipment->update([
                'status' => 'booked',
                'courier_name' => $selectedQuote->courier_name,
                'service_name' => $selectedQuote->service_name,
                'total_price_cents' => $selectedQuote->price_cents,
                'tracking_number' => $bookingResult['tracking_number'],
                'consignment_number' => $bookingResult['consignment_number'] ?? null,
                'label_url' => $bookingResult['label_url'] ?? null,
            ]);

            // 4. Handle Queue (Standardized Delay Logic)
            $delayMinutes = $mapping->carrierConfig->extra_settings['label_delay_minutes'] ?? 0;

            if ($delayMinutes > 0) {
                Log::info("Queueing label for {$shipment->courier_name} with {$delayMinutes}m delay.");
                \App\Jobs\GenerateShipmentLabel::dispatch($shipment)
                    ->delay(now()->addMinutes((int) $delayMinutes));
            } else {
                // Dispatch immediately. 
                // If label_url is already saved (e.g. Hunter), the Job will simply skip execution.
                \App\Jobs\GenerateShipmentLabel::dispatch($shipment);
            }

            // 5. ENTERPRISE ADDITION: Update Shopify (New Step 3 Logic)
            // If this shipment originated from Shopify, we must push the tracking number back.
            // This runs in the background so the user response is instant.
            if ($shipment->source === 'shopify') {
                \App\Jobs\UpdateShopifyFulfillment::dispatch($shipment);
            }

            return response()->json([
                'message' => 'Shipment booked successfully!',
                'shipment' => $shipment->fresh()
            ]);

        } catch (\Exception $e) {
            Log::error("Booking Error: " . $e->getMessage());
            return response()->json(['message' => 'Booking failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * SECURE LABEL DOWNLOAD
     */
    public function downloadLabel(Request $request, Shipment $shipment)
    {
        if ($request->user()->company_id !== $shipment->company_id) {
            abort(403, 'Unauthorized');
        }

        $filePath = $shipment->getRawOriginal('label_url');

        if (!$filePath || str_starts_with($filePath, 'http')) {
            abort(404, 'File not found on server storage.');
        }

        if (!\Illuminate\Support\Facades\Storage::disk('local')->exists($filePath)) {
            abort(404, 'File missing from secure storage.');
        }

        return \Illuminate\Support\Facades\Storage::disk('local')->download(
            $filePath,
            "Label-{$shipment->tracking_number}.pdf"
        );
    }
}