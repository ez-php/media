<?php

declare(strict_types=1);

namespace Tests;

use EzPhp\Media\GdDriver;
use EzPhp\Media\ImageProcessor;
use EzPhp\Media\ImageTransformerInterface;
use EzPhp\Media\ImagickDriver;
use EzPhp\Media\Media;
use EzPhp\Media\MediaServiceProvider;
use EzPhp\Storage\LocalDriver;
use EzPhp\Storage\StorageInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use Tests\Support\FakeConfig;
use Tests\Support\FakeContainer;

/**
 * Smoke test: MediaServiceProvider registers its bindings in a minimal
 * container context, selects the correct ImageTransformerInterface driver
 * based on the `media.driver` config key, and wires the Media facade.
 *
 * LocalDriver (ez-php/storage) is used below only as a real StorageInterface
 * instance to satisfy ImageProcessor's constructor — it is deliberately not
 * declared via #[UsesClass]: this module's phpunit.xml <source> covers only
 * its own src/, so a class from another package is "not a valid target for
 * code coverage" and, with failOnWarning=true, would fail the build.
 *
 * @uses \Tests\Support\FakeConfig
 * @uses \Tests\Support\FakeContainer
 */
#[CoversClass(MediaServiceProvider::class)]
#[UsesClass(GdDriver::class)]
#[UsesClass(ImagickDriver::class)]
#[UsesClass(ImageProcessor::class)]
#[UsesClass(Media::class)]
final class MediaServiceProviderTest extends TestCase
{
    protected function tearDown(): void
    {
        Media::resetInstance();
        parent::tearDown();
    }

    public function test_register_defaults_to_gd_driver(): void
    {
        $container = new FakeContainer(new FakeConfig([]));
        $container->instance(StorageInterface::class, new LocalDriver(sys_get_temp_dir()));

        $provider = new MediaServiceProvider($container);
        $provider->register();

        $this->assertTrue($container->wasBound(ImageTransformerInterface::class));
        $this->assertInstanceOf(GdDriver::class, $container->make(ImageTransformerInterface::class));
    }

    public function test_register_selects_imagick_driver_when_configured(): void
    {
        $container = new FakeContainer(new FakeConfig(['media.driver' => 'imagick']));
        $container->instance(StorageInterface::class, new LocalDriver(sys_get_temp_dir()));

        $provider = new MediaServiceProvider($container);
        $provider->register();

        $this->assertInstanceOf(ImagickDriver::class, $container->make(ImageTransformerInterface::class));
    }

    public function test_resolving_image_processor_sets_the_media_facade(): void
    {
        $container = new FakeContainer(new FakeConfig([]));
        $container->instance(StorageInterface::class, new LocalDriver(sys_get_temp_dir()));

        $provider = new MediaServiceProvider($container);
        $provider->register();

        $resolved = $container->make(ImageProcessor::class);

        // The binding factory wires the Media facade as a side effect.
        $this->assertSame($resolved, Media::getInstance());
    }
}
