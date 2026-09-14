<?php

declare(strict_types=1);

namespace Tests;

use EzPhp\Media\GdDriver;
use EzPhp\Media\MediaException;

/**
 * Class GdDriverTest
 *
 * @package Tests
 */
final class GdDriverTest extends TestCase
{
    private GdDriver $driver;

    protected function setUp(): void
    {
        $this->driver = new GdDriver();
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

    public function testResizePreservingAspectRatio(): void
    {
        $resized = $this->driver->resize(MediaPngFixture::make(20, 10), 10, 10);

        self::assertSame(['width' => 10, 'height' => 5], $this->driver->dimensions($resized));
    }

    public function testResizeWithoutPreservingAspectRatio(): void
    {
        $resized = $this->driver->resize(MediaPngFixture::make(20, 10), 5, 5, false);

        self::assertSame(['width' => 5, 'height' => 5], $this->driver->dimensions($resized));
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

        $this->driver->convertFormat(MediaPngFixture::make(), 'bmp');
    }

    public function testResizeThrowsOnInvalidData(): void
    {
        $this->expectException(MediaException::class);

        $this->driver->resize('not an image', 10, 10);
    }
}
