<?php

namespace App\Services\Couriers\Concerns;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait SavesLabels
{
    /**
     * Save raw label data SECURELY to a private disk.
     * Returns a relative path, NOT a public URL.
     */
    protected function saveLabelToDisk(string $content, string $carrierPrefix, bool $isBase64 = false): string
    {
        // 1. Decode content
        $fileContent = $isBase64 ? base64_decode($content) : $content;

        // 2. Generate secure filename
        // We use a random hash to prevent guessing
        $filename = 'labels/' . $carrierPrefix . '-' . Str::random(40) . '.pdf';

        // 3. ENTERPRISE FIX: Save to 'local' (Private), NOT 'public'
        // This file is now invisible to the outside world.
        Storage::disk('local')->put($filename, $fileContent);

        return $filename; // Return internal path, e.g., "labels/hunters-xyz.pdf"
    }
}