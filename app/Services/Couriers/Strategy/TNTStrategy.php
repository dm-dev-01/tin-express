<?php

namespace App\Services\Couriers\Strategy;

use App\Models\Shipment;
use App\Models\ShipmentQuote;
use App\Services\Couriers\Concerns\SavesLabels;
use App\Services\Couriers\CourierInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class TNTStrategy implements CourierInterface
{
    use SavesLabels;

    // --- STANDARD: Hardcoded Endpoints ---
    protected string $rttUrlTest = 'https://uat.tntexpress.com.au/Rttnet/inputRequest.aspx';
    protected string $rttUrlProd = 'https://www.tntexpress.com.au/Rtt/exrtt.asp';

    protected string $conUrlTest = 'https://uat.tntexpress.com.au/ws/v1.3/service.svc';
    protected string $conUrlProd = 'https://www.tntexpress.com.au/ws/v1.3/service.svc';

    protected string $trackUrlTest = 'https://uat.tntexpress.com.au/CCT/TrackResultsConPost.asp';
    protected string $trackUrlProd = 'https://www.tntexpress.com.au/CCT/TrackResultsConPost.asp';

    protected string $username;
    protected string $password;
    protected string $account;
    protected bool $isTest;
    protected array $settings;

    public function __construct(string $username, string $password, string $account, bool $isTest = true, array $settings = [])
    {
        $this->username = $username;
        $this->password = $password;
        $this->account = $account;
        $this->isTest = $isTest;
        $this->settings = $settings;
    }

    public function getCarrierCode(): string
    {
        return 'tnt';
    }

    // --- Helper to get correct URL based on Environment ---
    private function getUrl(string $type): string
    {
        return match($type) {
            'rtt' => $this->isTest ? $this->rttUrlTest : $this->rttUrlProd,
            'consignment' => $this->isTest ? $this->conUrlTest : $this->conUrlProd,
            'tracking' => $this->isTest ? $this->trackUrlTest : $this->trackUrlProd,
        };
    }

    public function getRates(array $data): array
    {
        $xml = $this->buildRttRequest($data);
        $url = $this->getUrl('rtt');

        Log::info("TNT RTT Request ($url)", ['xml_snippet' => substr($xml, 0, 200)]);

        try {
            // FIX: Manual Form Encoding
            // RTT.net documentation strictly requires 'application/x-www-form-urlencoded'
            // and often fails if the XML isn't perfectly encoded.
            
            $postData = http_build_query([
                'Username' => $this->username,
                'Password' => $this->password,
                'XMLRequest' => $xml
            ]);

            $response = Http::withHeaders([
                'Content-Type' => 'application/x-www-form-urlencoded'
            ])->send('POST', $url, [
                'body' => $postData
            ]);

            if ($response->failed()) {
                Log::error('TNT Rates API Failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return [];
            }

            return $this->parseRttResponse($response->body());

        } catch (Exception $e) {
            Log::error('TNT Rate Exception', ['error' => $e->getMessage()]);
            return [];
        }
    }

    public function createConsignment(Shipment $shipment, ShipmentQuote $quote): array
    {
        $prefix = $this->settings['prefix'] ?? 'TIX';
        $conNumber = $prefix . str_pad((string)$shipment->id, 9, '0', STR_PAD_LEFT);
        $serviceCode = $quote->service_code ?? '76'; // Default Road Express

        $xml = $this->buildConsignmentRequest($shipment, $conNumber, $serviceCode);
        $url = $this->getUrl('consignment');

        try {
            Log::info("TNT Booking ($conNumber) -> $url");

            $response = Http::withHeaders([
                'Content-Type' => 'text/xml; charset=utf-8',
                'SOAPAction' => 'http://tempuri.org/IConsignmentService/ProcessConsignmentRequest'
            ])->send('POST', $url, ['body' => $xml]);

            if ($response->failed()) {
                throw new Exception("TNT Booking HTTP Error: " . $response->status() . " Body: " . $response->body());
            }

            // Parse SOAP Response
            $cleanXml = str_ireplace(['soap:', 's:', 'a:', 'i:'], '', $response->body());
            $xmlObj = simplexml_load_string($cleanXml);

            $result = $xmlObj->Body->ProcessConsignmentRequestResponse->ProcessConsignmentRequestResult ?? null;

            if (!$result) throw new Exception("Invalid XML Response");

            if (isset($result->Error)) {
                throw new Exception("TNT API Error: " . ($result->Error->Description ?? 'Unknown'));
            }

            $trackingNumber = (string)$result->ConsignmentProcessResultList->ConsignmentProcessResult->ConsignmentNumber;
            $labelBase64 = (string)$result->ConsignmentProcessResultList->ConsignmentProcessResult->Label;

            // Save Label
            $labelUrl = $this->saveLabelFromBase64($labelBase64, "tnt-$trackingNumber");

            return [
                'tracking_number' => $trackingNumber,
                'consignment_number' => $conNumber,
                'label_url' => $labelUrl,
                'carrier_ref' => $trackingNumber
            ];

        } catch (Exception $e) {
            Log::error("TNT Booking Failed: " . $e->getMessage());
            throw $e;
        }
    }

    public function generateLabel(string $consignmentNumber): ?string
    {
        return null; // TNT returns label on creation. No separate fetch needed yet.
    }

    public function trackShipment(string $trackingNumber): array
    {
        try {
            $user = $this->settings['tracking_user'] ?? '';
            $pass = $this->settings['tracking_password'] ?? '';
            $url = $this->getUrl('tracking');

            $response = Http::asForm()->post($url, [
                'User' => $user,
                'Password' => $pass,
                'Con' => $trackingNumber
            ]);

            $html = $response->body();
            $status = 'Unknown';

            if (stripos($html, 'Delivered') !== false) $status = 'Delivered';
            elseif (stripos($html, 'In Transit') !== false) $status = 'In Transit';
            elseif (stripos($html, 'Collected') !== false) $status = 'Picked Up';

            return [
                'status' => $status,
                'consignment_number' => $trackingNumber,
                'history' => []
            ];

        } catch (Exception $e) {
            return ['status' => 'Error', 'message' => $e->getMessage()];
        }
    }

    // --- XML BUILDERS (Private) ---
    private function buildRttRequest(array $data): string
    {
        $date = now()->format('Y-m-d');
        $linesXml = '';
        foreach ($data['items'] as $item) {
            // RTT: Dimensions in Metres, Weight in KG
            $l = $item['length'] / 100; $w = $item['width'] / 100; $h = $item['height'] / 100;
            $linesXml .= "<consignmentLine>
                <dimensionsLength>{$l}</dimensionsLength>
                <dimensionsWidth>{$w}</dimensionsWidth>
                <dimensionsHeight>{$h}</dimensionsHeight>
                <weight>{$item['weight']}</weight>
                <quantity>{$item['quantity']}</quantity>
                <description>Carton</description>
            </consignmentLine>";
        }
        // NOTE: Account code passed inside XML, but Auth usually handled by Basic Auth or Header
        return "<?xml version=\"1.0\" standalone=\"yes\"?><ratingRequest><sender><suburb>{$data['sender_suburb']}</suburb><postcode>{$data['sender_postcode']}</postcode><state>{$data['sender_state']}</state><account>{$this->account}</account></sender><receiver><suburb>{$data['receiver_suburb']}</suburb><postcode>{$data['receiver_postcode']}</postcode><state>{$data['receiver_state']}</state></receiver><consignment><shippingDate>{$date}</shippingDate><action>RATE</action>{$linesXml}</consignment></ratingRequest>";
    }

    private function parseRttResponse($xmlString): array
    {
        $rates = [];
        try {
            $xml = simplexml_load_string($xmlString);
            // Check if response is valid and has products
            if ($xml && isset($xml->ratedProduct)) {
                foreach ($xml->ratedProduct as $product) {
                    $rates[] = [
                        'courier_name' => 'TNT',
                        'service_name' => (string)$product->product->description,
                        'service_code' => (string)$product->product->code,
                        'price_cents'  => (int)round((float)$product->quote->price * 100),
                        'currency'     => 'AUD',
                        'eta'          => isset($product->estimatedDeliveryDateTime) ? date('Y-m-d', strtotime((string)$product->estimatedDeliveryDateTime)) : 'N/A',
                    ];
                }
            } else {
                Log::warning('TNT RTT Parse: No ratedProduct found', ['xml_snippet' => substr($xmlString, 0, 200)]);
            }
        } catch (Exception $e) {
            Log::error('TNT RTT XML Parse Error: ' . $e->getMessage());
        }
        return $rates;
    }

    private function buildConsignmentRequest(Shipment $shipment, $conNumber, $serviceCode): string
    {
        $linesXml = ''; $i = 1;
        foreach ($shipment->items as $item) {
            $l = $item->length / 100; $w = $item->width / 100; $h = $item->height / 100;
            $vol = number_format($l * $w * $h * $item->quantity, 4);
            $linesXml .= "<ConsignmentLine><LineNumber>{$i}</LineNumber><DescriptionOfGoods>Freight</DescriptionOfGoods><PackageType>CTN</PackageType><NumberOfUnits>{$item->quantity}</NumberOfUnits><Length>{$l}</Length><Width>{$w}</Width><Height>{$h}</Height><Weight>{$item->weight}</Weight><CubicVolume>{$vol}</CubicVolume></ConsignmentLine>";
            $i++;
        }
        $sName = htmlspecialchars($shipment->sender_name); $sAddr = htmlspecialchars($shipment->sender_address);
        $rName = htmlspecialchars($shipment->receiver_name); $rAddr = htmlspecialchars($shipment->receiver_address);
        
        return "<s:Envelope xmlns:s=\"http://schemas.xmlsoap.org/soap/envelope/\"><s:Body><ProcessConsignmentRequest xmlns=\"http://tempuri.org/\"><securityToken><Username xmlns=\"http://schemas.datacontract.org/2004/07/TNT.DataContracts.Security\">{$this->username}</Username><Password xmlns=\"http://schemas.datacontract.org/2004/07/TNT.DataContracts.Security\">{$this->password}</Password></securityToken><consignmentRequest><ConsignmentList xmlns=\"http://schemas.datacontract.org/2004/07/TNT.DataContracts.Consignment\"><Consignment><ConsignmentNumber>{$conNumber}</ConsignmentNumber><CustomerRef>{$shipment->reference}</CustomerRef><Sender><Name>{$sName}</Name><AddressLine1>{$sAddr}</AddressLine1><Suburb>{$shipment->sender_suburb}</Suburb><Postcode>{$shipment->sender_postcode}</Postcode><State>{$shipment->sender_state}</State><Country>AU</Country><AccountNumber>{$this->account}</AccountNumber></Sender><Receiver><Name>{$rName}</Name><AddressLine1>{$rAddr}</AddressLine1><Suburb>{$shipment->receiver_suburb}</Suburb><Postcode>{$shipment->receiver_postcode}</Postcode><State>{$shipment->receiver_state}</State><Country>AU</Country></Receiver><ServiceCode>{$serviceCode}</ServiceCode><Payer>S</Payer><ConsignmentLines>{$linesXml}</ConsignmentLines></Consignment></ConsignmentList><LabelGenerationRequest xmlns=\"http://schemas.datacontract.org/2004/07/TNT.DataContracts.Consignment\"><ConsignmentNumber>{$conNumber}</ConsignmentNumber><LabelType>PDF</LabelType></LabelGenerationRequest></consignmentRequest></ProcessConsignmentRequest></s:Body></s:Envelope>";
    }
}