<?php

declare(strict_types=1);

namespace Tests;

use EzPhp\Media\GdDriver;
use EzPhp\Media\ImageProcessor;
use EzPhp\Storage\InMemoryDriver;

/**
 * Class ImageProcessorTest
 *
 * @package Tests
 */
final class ImageProcessorTest extends TestCase
{
    private InMemoryDriver $storage;

    private ImageProcessor $processor;

    protected function setUp(): void
    {
        $this->storage = new InMemoryDriver();
        $this->processor = new ImageProcessor($this->storage, new GdDriver());
    }

    public function testResizeReadsFromAndWritesToStorage(): void
    {
        $this->storage->put('source.png', MediaPngFixture::make(20, 10));

        $this->processor->resize('source.png', 'resized.png', 10, 10, false);

        self::assertSame(['width' => 10, 'height' => 10], $this->processor->dimensions('resized.png'));
    }

    public function testCropReadsFromAndWritesToStorage(): void
    {
        $this->storage->put('source.png', MediaPngFixture::make(20, 10));

        $this->processor->crop('source.png', 'cropped.png', 0, 0, 5, 5);

        self::assertSame(['width' => 5, 'height' => 5], $this->processor->dimensions('cropped.png'));
    }

    public function testConvertFormatReadsFromAndWritesToStorage(): void
    {
        $this->storage->put('source.png', MediaPngFixture::make());

        $this->processor->convertFormat('source.png', 'converted.jpg', 'jpeg');

        self::assertStringStartsWith("\xFF\xD8\xFF", $this->storage->get('converted.jpg'));
    }

    public function testDimensionsReadsFromStorage(): void
    {
        $this->storage->put('source.png', MediaPngFixture::make(20, 10));

        self::assertSame(['width' => 20, 'height' => 10], $this->processor->dimensions('source.png'));
    }
}
