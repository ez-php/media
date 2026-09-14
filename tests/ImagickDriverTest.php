<?php

declare(strict_types=1);

namespace Tests;

use EzPhp\Media\ImagickDriver;
use EzPhp\Media\MediaException;

/**
 * Class ImagickDriverTest
 *
 * @package Tests
 */
final class ImagickDriverTest extends TestCase
{
    private ImagickDriver $driver;

    protected function setUp(): void
    {
        if (!extension_loaded('imagick')) {
            self::markTestSkipped('ext-imagick is not installed.');
        }

        $this->driver = new ImagickDriver();
    }

    public function testDimensionsReturnsWidthAndHeight(): void
    {
        $dimensions = $this->driver->dimensions(MediaPngFixture::make(20, 10));

        self::assertSame(['width' => 20, 'height' => 10], $dimensions);
    }

    public function testDimensionsThrowsOnInvalidData(): void
    {
        $this->expectException(MediaException::class);

        $this->driver->dimensions('not an image');
    }

    public function testResize(): void
    {
        $resized = $this->driver->resize(MediaPngFixture::make(20, 10), 10, 10, false);

        self::assertSame(['width' => 10, 'height' => 10], $this->driver->dimensions($resized));
    }

    public function testCrop(): void
    {
        $cropped = $this->driver->crop(MediaPngFixture::make(20, 10), 0, 0, 5, 5);

        self::assertSame(['width' => 5, 'height' => 5], $this->driver->dimensions($cropped));
    }

    public function testConvertFormat(): void
    {
        $converted = $this->driver->convertFormat(MediaPngFixture::make(), 'jpeg');

        self::assertStringStartsWith("\xFF\xD8\xFF", $converted);
    }

    public function testConvertFormatThrowsOnUnsupportedFormat(): void
    {
        $this->expectException(MediaException::class);

        $this->driver->convertFormat(MediaPngFixture::make(), 'not-a-format');
    }
}
