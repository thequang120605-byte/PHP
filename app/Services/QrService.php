<?php

namespace App\Services;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class QrService
{
    public static function svg(string $text, int $size = 300): string
    {
        $writer = new Writer(new ImageRenderer(new RendererStyle($size, 4), new SvgImageBackEnd));

        return preg_replace('/<\?xml[^>]*\?>/', '', $writer->writeString($text));
    }
}
