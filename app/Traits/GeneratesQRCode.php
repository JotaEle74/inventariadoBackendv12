<?php

namespace App\Traits;

use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Storage;

trait GeneratesQRCode
{
    /**
     * Generate QR code for an asset
     *
     * @param string $data Data to encode in QR
     * @param string $filename Filename to save the QR code
     * @return string Path to the generated QR code
     */
    protected function generateQRCode(string $data, string $filename): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle(400),
            new ImagickImageBackEnd()
        );
        
        $writer = new Writer($renderer);
        $qrCode = $writer->writeString($data);

        $path = 'qrcodes/' . $filename . '.png';
        Storage::put('public/' . $path, $qrCode);

        return $path;
    }

    /**
     * Get QR code URL for an asset
     *
     * @param string $path Path to the QR code
     * @return string Full URL to the QR code
     */
    protected function getQRCodeUrl(string $path): string
    {
        return Storage::url($path);
    }
} 