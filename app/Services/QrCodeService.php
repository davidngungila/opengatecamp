<?php

namespace App\Services;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Output\QRGdImagePNG;

class QrCodeService
{
    /**
     * Render data as a base64 PNG data URI safe to embed in dompdf HTML.
     */
    public function pngDataUri(string $data, int $scale = 4): string
    {
        try {
            $options = new QROptions([
                'outputInterface' => QRGdImagePNG::class,
                'scale'          => $scale,
                'imageBase64'    => true,
                'addQuietzone'   => true,
            ]);

            return (new QRCode($options))->render($data);
        } catch (\Throwable $e) {
            return '';
        }
    }

    public function svgDataUri(string $data, int $scale = 5): string
    {
        try {
            $options = new QROptions([
                'outputInterface' => \chillerlan\QRCode\Output\QRMarkupSVG::class,
                'scale'          => $scale,
                'imageBase64'    => true,
                'addQuietzone'   => true,
            ]);

            return (new QRCode($options))->render($data);
        } catch (\Throwable $e) {
            return '';
        }
    }
}