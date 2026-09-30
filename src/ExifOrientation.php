<?php

declare(strict_types=1);

namespace EzPhp\Media;

/**
 * Class ExifOrientation
 *
 * Reads only the EXIF Orientation tag (IFD0, 0x0112) of JPEG data — enough to
 * turn phone photos upright — without ext-exif. Anything that isn't a JPEG with
 * a well-formed Exif APP1 segment yields 1 (no transformation).
 *
 * @internal Used by GdDriver; ImagickDriver reads the tag through Imagick.
 * @package EzPhp\Media
 */
final class ExifOrientation
{
    /**
     * @param string $contents Encoded image data.
     *
     * @return int 1–8; 1 when absent or unreadable.
     */
    public static function fromJpeg(string $contents): int
    {
        if (!str_starts_with($contents, "\xFF\xD8")) {
            return 1;
        }

        $offset = 2;
        $length = strlen($contents);

        // Walk the marker segments up to the start of the image data.
        while ($offset + 4 <= $length && $contents[$offset] === "\xFF") {
            $marker = ord($contents[$offset + 1]);

            if ($marker === 0xDA || $marker === 0xD9) {
                break;
            }

            $size = self::u16($contents, $offset + 2, false);

            if ($size < 2) {
                break;
            }

            if ($marker === 0xE1 && substr($contents, $offset + 4, 6) === "Exif\0\0") {
                return self::fromTiff(substr($contents, $offset + 10, $size - 8));
            }

            $offset += 2 + $size;
        }

        return 1;
    }

    /**
     * @param string $tiff TIFF header and IFDs from the Exif segment.
     *
     * @return int
     */
    private static function fromTiff(string $tiff): int
    {
        $order = substr($tiff, 0, 2);

        if ($order !== 'II' && $order !== 'MM' || strlen($tiff) < 8) {
            return 1;
        }

        $little = $order === 'II';
        $ifd = self::u32($tiff, 4, $little);

        if ($ifd + 2 > strlen($tiff)) {
            return 1;
        }

        $entries = self::u16($tiff, $ifd, $little);

        for ($i = 0; $i < $entries; $i++) {
            $entry = $ifd + 2 + $i * 12;

            if ($entry + 12 > strlen($tiff)) {
                return 1;
            }

            if (self::u16($tiff, $entry, $little) === 0x0112) {
                $value = self::u16($tiff, $entry + 8, $little);

                return $value >= 1 && $value <= 8 ? $value : 1;
            }
        }

        return 1;
    }

    private static function u16(string $data, int $offset, bool $little): int
    {
        $unpacked = unpack($little ? 'v' : 'n', $data, $offset);

        return is_array($unpacked) && is_int($unpacked[1] ?? null) ? $unpacked[1] : 0;
    }

    private static function u32(string $data, int $offset, bool $little): int
    {
        $unpacked = unpack($little ? 'V' : 'N', $data, $offset);

        return is_array($unpacked) && is_int($unpacked[1] ?? null) ? $unpacked[1] : 0;
    }
}
