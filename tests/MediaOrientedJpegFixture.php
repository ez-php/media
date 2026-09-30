<?php

declare(strict_types=1);

namespace Tests;

/**
 * Builds a 40x20 JPEG with four solid quadrants — top-left red, top-right blue,
 * bottom-left green, bottom-right yellow — and an EXIF Orientation tag, so a
 * test can tell exactly how the pixels were turned.
 */
final class MediaOrientedJpegFixture
{
    public const array RED = [255, 0, 0];

    public const array BLUE = [0, 0, 255];

    public const array GREEN = [0, 255, 0];

    public const array YELLOW = [255, 255, 0];

    public static function make(int $orientation, bool $littleEndian = false): string
    {
        $image = imagecreatetruecolor(40, 20);
        self::assertImage($image);

        foreach ([[0, 0, self::RED], [20, 0, self::BLUE], [0, 10, self::GREEN], [20, 10, self::YELLOW]] as [$x, $y, $rgb]) {
            $color = imagecolorallocate($image, $rgb[0], $rgb[1], $rgb[2]);
            imagefilledrectangle($image, $x, $y, $x + 19, $y + 9, (int) $color);
        }

        ob_start();
        imagejpeg($image, null, 100);
        $jpeg = (string) ob_get_clean();

        return substr($jpeg, 0, 2) . self::exifSegment($orientation, $littleEndian) . substr($jpeg, 2);
    }

    /**
     * APP1 segment holding a TIFF header and one IFD0 entry: Orientation (0x0112, SHORT).
     */
    private static function exifSegment(int $orientation, bool $littleEndian): string
    {
        $s = $littleEndian ? 'v' : 'n'; // 16-bit
        $l = $littleEndian ? 'V' : 'N'; // 32-bit

        $tiff = ($littleEndian ? 'II' : 'MM') . pack($s, 42) . pack($l, 8)
            . pack($s, 1)                                       // one entry
            . pack($s, 0x0112) . pack($s, 3) . pack($l, 1)      // Orientation, SHORT, count 1
            . pack($s, $orientation) . pack($s, 0)              // value, padding
            . pack($l, 0);                                      // no next IFD

        $payload = "Exif\0\0" . $tiff;

        return "\xFF\xE1" . pack('n', strlen($payload) + 2) . $payload;
    }

    private static function assertImage(mixed $image): void
    {
        if (!$image instanceof \GdImage) {
            throw new \RuntimeException('GD could not allocate the fixture image.');
        }
    }
}
