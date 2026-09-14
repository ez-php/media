<?php

declare(strict_types=1);

namespace EzPhp\Media;

/**
 * Class Media
 *
 * Static façade for the active ImageProcessor instance.
 * Wired by MediaServiceProvider via setInstance().
 *
 * @package EzPhp\Media
 */
final class Media
{
    private static ?ImageProcessor $instance = null;

    /**
     * Wire the active image processor instance (called by MediaServiceProvider).
     *
     * @param ImageProcessor $processor
     *
     * @return void
     */
    public static function setInstance(ImageProcessor $processor): void
    {
        self::$instance = $processor;
    }

    /**
     * Return the active image processor instance.
     *
     * @throws MediaException If no instance has been set.
     *
     * @return ImageProcessor
     */
    public static function getInstance(): ImageProcessor
    {
        if (self::$instance === null) {
            throw new MediaException(
                'Media instance not set. Did you register MediaServiceProvider?'
            );
        }

        return self::$instance;
    }

    /**
     * Reset the active instance (primarily for testing).
     *
     * @return void
     */
    public static function resetInstance(): void
    {
        self::$instance = null;
    }

    /**
     * @param string $sourcePath
     * @param string $destPath
     * @param int    $width
     * @param int    $height
     * @param bool   $preserveAspectRatio
     *
     * @return void
     */
    public static function resize(
        string $sourcePath,
        string $destPath,
        int $width,
        int $height,
        bool $preserveAspectRatio = true,
    ): void {
        self::getInstance()->resize($sourcePath, $destPath, $width, $height, $preserveAspectRatio);
    }

    /**
     * @param string $sourcePath
     * @param string $destPath
     * @param int    $x
     * @param int    $y
     * @param int    $width
     * @param int    $height
     *
     * @return void
     */
    public static function crop(string $sourcePath, string $destPath, int $x, int $y, int $width, int $height): void
    {
        self::getInstance()->crop($sourcePath, $destPath, $x, $y, $width, $height);
    }

    /**
     * @param string $sourcePath
     * @param string $destPath
     * @param string $format
     * @param int    $quality
     *
     * @return void
     */
    public static function convertFormat(string $sourcePath, string $destPath, string $format, int $quality = 90): void
    {
        self::getInstance()->convertFormat($sourcePath, $destPath, $format, $quality);
    }

    /**
     * @param string $path
     *
     * @return array{width: int, height: int}
     */
    public static function dimensions(string $path): array
    {
        return self::getInstance()->dimensions($path);
    }
}
