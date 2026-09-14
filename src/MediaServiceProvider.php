<?php

declare(strict_types=1);

namespace EzPhp\Media;

use EzPhp\Contracts\ConfigInterface;
use EzPhp\Contracts\ContainerInterface;
use EzPhp\Contracts\ServiceProvider;
use EzPhp\Storage\StorageInterface;

/**
 * Class MediaServiceProvider
 *
 * Reads the media configuration and binds the active ImageTransformerInterface
 * driver and ImageProcessor to the container. Also wires the Media static façade.
 *
 * Supported drivers: gd (default), imagick.
 *
 * @package EzPhp\Media
 */
final class MediaServiceProvider extends ServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register(): void
    {
        $this->app->bind(
            ImageTransformerInterface::class,
            function (ContainerInterface $app): ImageTransformerInterface {
                $config = $app->make(ConfigInterface::class);
                $driver = $config->get('media.driver', 'gd');
                $driver = is_string($driver) ? $driver : 'gd';

                return match ($driver) {
                    'imagick' => new ImagickDriver(),
                    default => new GdDriver(),
                };
            },
        );

        $this->app->bind(
            ImageProcessor::class,
            function (ContainerInterface $app): ImageProcessor {
                $processor = new ImageProcessor(
                    $app->make(StorageInterface::class),
                    $app->make(ImageTransformerInterface::class),
                );

                Media::setInstance($processor);

                return $processor;
            },
        );
    }
}
