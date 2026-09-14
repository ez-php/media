<?php

declare(strict_types=1);

namespace EzPhp\Media;

use EzPhp\Storage\StorageInterface;

/**
 * Class ImageProcessor
 *
 * Applies image transformations to files read from and written back to a
 * StorageInterface backend, via a pluggable ImageTransformerInterface driver.
 *
 * @package EzPhp\Media
 */
final class ImageProcessor
{
    /**
     * @param StorageInterface          $storage     Source and destination file storage.
     * @param ImageTransformerInterface $transformer Raw image transformation driver.
     */
    public function __construct(
        private readonly StorageInterface $storage,
        private readonly ImageTransformerInterface $transformer,
    ) {
    }

    /**
     * Resize an image file and write the result to a new path.
     *
     * @param string $sourcePath          Path of the source image, resolved via StorageInterface.
     * @param string $destPath            Path the resized image is written to.
     * @param int    $width               Target width in pixels.
     * @param int    $height              Target height in pixels.
     * @param bool   $preserveAspectRatio When true, the image is scaled to fit within
     *                                    width x height while preserving its aspect ratio.
     *
     * @throws MediaException If the source image cannot be decoded or resized.
     *
     * @return void
     */
    public function resize(
        string $sourcePath,
        string $destPath,
        int $width,
        int $height,
        bool $preserveAspectRatio = true,
    ): void {
        $resized = $this->transformer->resize(
            $this->storage->get($sourcePath),
            $width,
            $height,
            $preserveAspectRatio,
        );

        $this->storage->put($destPath, $resized);
    }

    /**
     * Crop a rectangular region out of an image file and write the result to a new path.
     *
     * @param string $sourcePath Path of the source image, resolved via StorageInterface.
     * @param string $destPath   Path the cropped image is written to.
     * @param int    $x          Left offset of the crop region, in pixels.
     * @param int    $y          Top offset of the crop region, in pixels.
     * @param int    $width      Crop region width in pixels.
     * @param int    $height     Crop region height in pixels.
     *
     * @throws MediaException If the source image cannot be decoded or cropped.
     *
     * @return void
     */
    public function crop(string $sourcePath, string $destPath, int $x, int $y, int $width, int $height): void
    {
        $cropped = $this->transformer->crop($this->storage->get($sourcePath), $x, $y, $width, $height);

        $this->storage->put($destPath, $cropped);
    }

    /**
     * Convert an image file to a different format and write the result to a new path.
     *
     * @param string $sourcePath Path of the source image, resolved via StorageInterface.
     * @param string $destPath   Path the converted image is written to.
     * @param string $format     Target format: "jpeg", "png", "webp", or "gif".
     * @param int    $quality    Compression quality, 0-100 (ignored for png/gif).
     *
     * @throws MediaException If the source image cannot be decoded or the format is unsupported.
     *
     * @return void
     */
    public function convertFormat(string $sourcePath, string $destPath, string $format, int $quality = 90): void
    {
        $converted = $this->transformer->convertFormat($this->storage->get($sourcePath), $format, $quality);

        $this->storage->put($destPath, $converted);
    }

    /**
     * Return the pixel dimensions of an image file.
     *
     * @param string $path Path of the image, resolved via StorageInterface.
     *
     * @throws MediaException If the image cannot be decoded.
     *
     * @return array{width: int, height: int}
     */
    public function dimensions(string $path): array
    {
        return $this->transformer->dimensions($this->storage->get($path));
    }
}
