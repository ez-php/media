<?php

declare(strict_types=1);

namespace Tests;

use EzPhp\Media\GdDriver;
use EzPhp\Media\ImageProcessor;
use EzPhp\Media\Media;
use EzPhp\Media\MediaException;
use EzPhp\Storage\InMemoryDriver;

/**
 * Class MediaTest
 *
 * @package Tests
 */
final class MediaTest extends TestCase
{
    private InMemoryDriver $storage;

    protected function setUp(): void
    {
        $this->storage = new InMemoryDriver();
        Media::setInstance(new ImageProcessor($this->storage, new GdDriver()));
    }

    protected function tearDown(): void
    {
        Media::resetInstance();
    }

    public function testGetInstanceThrowsWhenNotSet(): void
    {
        Media::resetInstance();

        $this->expectException(MediaException::class);

        Media::getInstance();
    }

    public function testResizeDelegatesToInstance(): void
    {
        $this->storage->put('source.png', MediaPngFixture::make(20, 10));

        Media::resize('source.png', 'resized.png', 10, 10, false);

        self::assertSame(['width' => 10, 'height' => 10], Media::dimensions('resized.png'));
    }

    public function testCropDelegatesToInstance(): void
    {
        $this->storage->put('source.png', MediaPngFixture::make(20, 10));

        Media::crop('source.png', 'cropped.png', 0, 0, 5, 5);

        self::assertSame(['width' => 5, 'height' => 5], Media::dimensions('cropped.png'));
    }

    public function testConvertFormatDelegatesToInstance(): void
    {
        $this->storage->put('source.png', MediaPngFixture::make());

        Media::convertFormat('source.png', 'converted.jpg', 'jpeg');

        self::assertStringStartsWith("\xFF\xD8\xFF", $this->storage->get('converted.jpg'));
    }
}
