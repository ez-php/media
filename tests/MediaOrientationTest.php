<?php

declare(strict_types=1);

namespace Tests;

use EzPhp\Media\ExifOrientation;
use EzPhp\Media\GdDriver;
use EzPhp\Media\ImageTransformerInterface;
use EzPhp\Media\ImagickDriver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * EXIF orientation is applied before any transformation, so phone photos come
 * out upright; covered for both drivers against all eight orientations.
 *
 * @package Tests
 * @uses \Tests\MediaOrientedJpegFixture
 */
#[CoversClass(ExifOrientation::class)]
#[CoversClass(GdDriver::class)]
#[CoversClass(ImagickDriver::class)]
final class MediaOrientationTest extends TestCase
{
    public function test_reads_the_orientation_tag_in_both_byte_orders(): void
    {
        self::assertSame(6, ExifOrientation::fromJpeg(MediaOrientedJpegFixture::make(6)));
        self::assertSame(8, ExifOrientation::fromJpeg(MediaOrientedJpegFixture::make(8, littleEndian: true)));
    }

    public function test_missing_or_foreign_data_means_orientation_one(): void
    {
        self::assertSame(1, ExifOrientation::fromJpeg(MediaPngFixture::make(4, 4)));
        self::assertSame(1, ExifOrientation::fromJpeg("\xFF\xD8\xFF\xE1\x00\x04ab"));
        self::assertSame(1, ExifOrientation::fromJpeg(''));
    }

    /**
     * Expected quadrant colours (TL, TR, BL, BR) of the upright image, and its size.
     *
     * @return array<string, array{int, list<list<int>>, array{width: int, height: int}}>
     */
    public static function orientations(): array
    {
        $r = MediaOrientedJpegFixture::RED;
        $b = MediaOrientedJpegFixture::BLUE;
        $g = MediaOrientedJpegFixture::GREEN;
        $y = MediaOrientedJpegFixture::YELLOW;
        $landscape = ['width' => 40, 'height' => 20];
        $portrait = ['width' => 20, 'height' => 40];

        return [
            '1 normal' => [1, [$r, $b, $g, $y], $landscape],
            '2 mirror horizontal' => [2, [$b, $r, $y, $g], $landscape],
            '3 rotate 180' => [3, [$y, $g, $b, $r], $landscape],
            '4 mirror vertical' => [4, [$g, $y, $r, $b], $landscape],
            '5 transpose' => [5, [$r, $g, $b, $y], $portrait],
            '6 rotate 90 cw' => [6, [$g, $r, $y, $b], $portrait],
            '7 transverse' => [7, [$y, $b, $g, $r], $portrait],
            '8 rotate 90 ccw' => [8, [$b, $y, $r, $g], $portrait],
        ];
    }

    /**
     * @param list<list<int>>                   $quadrants
     * @param array{width: int, height: int}    $size
     */
    #[DataProvider('orientations')]
    public function test_gd_driver_normalises_orientation(int $orientation, array $quadrants, array $size): void
    {
        $this->assertUpright(new GdDriver(), $orientation, $quadrants, $size);
    }

    /**
     * @param list<list<int>>                   $quadrants
     * @param array{width: int, height: int}    $size
     */
    #[DataProvider('orientations')]
    public function test_imagick_driver_normalises_orientation(int $orientation, array $quadrants, array $size): void
    {
        if (!extension_loaded('imagick')) {
            self::markTestSkipped('ext-imagick is not installed.');
        }

        $this->assertUpright(new ImagickDriver(), $orientation, $quadrants, $size);
    }

    /**
     * @param list<list<int>>                   $quadrants
     * @param array{width: int, height: int}    $size
     */
    private function assertUpright(ImageTransformerInterface $driver, int $orientation, array $quadrants, array $size): void
    {
        $source = MediaOrientedJpegFixture::make($orientation);

        self::assertSame($size, $driver->dimensions($source));
        // The output is upright and not tagged for another turn.
        self::assertSame($size, $driver->dimensions($driver->convertFormat($source, 'jpeg')));

        // A same-size PNG conversion goes through decode() without resampling.
        $png = imagecreatefromstring($driver->convertFormat($source, 'png'));
        self::assertInstanceOf(\GdImage::class, $png);
        self::assertSame($size['width'], imagesx($png));

        $w = imagesx($png);
        $h = imagesy($png);
        $points = [[intdiv($w, 4), intdiv($h, 4)], [intdiv(3 * $w, 4), intdiv($h, 4)], [intdiv($w, 4), intdiv(3 * $h, 4)], [intdiv(3 * $w, 4), intdiv(3 * $h, 4)]];

        foreach ($points as $i => [$x, $y]) {
            // imagecolorsforindex() also resolves palette PNGs (Imagick may write one).
            $color = imagecolorsforindex($png, (int) imagecolorat($png, $x, $y));
            $actual = [$color['red'], $color['green'], $color['blue']];

            foreach ([0, 1, 2] as $channel) {
                self::assertEqualsWithDelta($quadrants[$i][$channel], $actual[$channel], 60, "orientation {$orientation}, quadrant {$i}");
            }
        }
    }
}
