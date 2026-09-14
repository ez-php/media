<?php

declare(strict_types=1);

namespace Tests;

/**
 * Class MediaPngFixture
 *
 * Builds small in-memory PNG fixtures for image transformation tests.
 *
 * Deliberately not a method on TestCase: the shared `Tests\` PSR-4 mapping
 * resolves a bare class name against every registered module's tests/
 * directory in composer.json order and returns the first match, so a
 * `Tests\TestCase` this module defines is never guaranteed to be the file
 * that actually loads. A uniquely named class avoids that ambiguity.
 *
 * @package Tests
 */
final class MediaPngFixture
{
    /**
     * @param int $width
     * @param int $height
     *
     * @return string Encoded PNG data.
     */
    public static function make(int $width = 20, int $height = 10): string
    {
        $image = imagecreatetruecolor(max(1, $width), max(1, $height));
        $red = imagecolorallocate($image, 255, 0, 0);

        if ($red === false) {
            $red = 0;
        }

        imagefilledrectangle($image, 0, 0, $width - 1, $height - 1, $red);

        ob_start();
        imagepng($image);
        $data = ob_get_clean();

        return $data === false ? '' : $data;
    }
}
