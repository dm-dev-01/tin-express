<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class CourierMapping extends Model
{
    protected $guarded = ['id'];

    /**
     * Resolve a mapping efficiently using Cache.
     */

    public function carrierConfig(): BelongsTo
    {
        return $this->belongsTo(CarrierConfig::class);
    }
    public static function resolve(string $courier, string $field, string $input): ?array
    {
        $input = strtolower(trim($input));
        
        // Cache key to prevent hitting DB for every single item in a loop
        $cacheKey = "mapping_{$courier}_{$field}_{$input}";

        return Cache::remember($cacheKey, 3600, function () use ($courier, $field, $input) {
            $mapping = self::where('courier_code', $courier)
                ->where('target_field', $field)
                ->where('input_value', $input)
                ->first();

            return $mapping ? [
                'code' => $mapping->output_code,
                'description' => $mapping->output_description
            ] : null;
        });
    }
}