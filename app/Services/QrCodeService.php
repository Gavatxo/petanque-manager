<?php

namespace App\Services;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\Result\ResultInterface;
use Endroid\QrCode\Writer\SvgWriter;

/**
 * Génère des QR codes en SVG (pur PHP, sans extension d'image).
 */
class QrCodeService
{
    public function svg(string $data, int $size = 320): string
    {
        return $this->build($data, $size)->getString();
    }

    /**
     * QR code encodé en data URI, prêt à être injecté dans un attribut src.
     */
    public function dataUri(string $data, int $size = 320): string
    {
        return $this->build($data, $size)->getDataUri();
    }

    private function build(string $data, int $size): ResultInterface
    {
        return (new Builder(
            writer: new SvgWriter,
            data: $data,
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: $size,
            margin: 8,
        ))->build();
    }
}
