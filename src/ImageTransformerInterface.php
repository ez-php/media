<?php

declare(strict_types=1);

namespace EzPhp\Media;

/**
 * Interface ImageTransformerInterface
 *
 * Contract for raw image transformation operations. Implementations operate
 * on in-memory encoded image data (bytes in, bytes out) and know nothing
 * about storage — reading and writing files is StorageInterface's job.
 *
 * @package EzPhp\Media
 */
interface ImageTransformerInterface
{
    /**
     * Resize image data to the given dimensions.
     *
     * @param string $contents            Encoded source image data.
     * @param int    $width               Target width in pixels.
     * @param int    $height              Target height in pixels.
     * @param bool   $preserveAspectRatio When true, the image is scaled to fit within
     *                                    width x height while preserving its aspect ratio.
     *
     * @throws MediaException If the source data cannot be decoded.
     *
     * @return string Encoded resized image data, same format as the source.
     */
    public function resize(string $contents, int $width, int $height, bool $preserveAspectRatio = true): string;

    /**
     * Crop a rectangular region out of image data.
     *
     * @param string $contents Encoded source image data.
     * @param int    $x        Left offset of the crop region, in pixels.
     * @param int    $y        Top offset of the crop region, in pixels.
     * @param int    $width    Crop region width in pixels.
     * @param int    $height   Crop region height in pixels.
     *
     * @throws MediaException If the source data cannot be decoded.
     *
     * @return string Encoded cropped image data, same format as the source.
     */
    public function crop(string $contents, int $x, int $y, int $width, int $height): string;

    /**
     * Convert image data to a different format.
     *
     * @param string $contents Encoded source image data.
     * @param string $format   Target format: "jpeg", "png", "webp", or "gif".
     * @param int    $quality  Compression quality, 0-100 (ignored for png/gif).
     *
     * @throws MediaException If the source data cannot be decoded or the format is unsupported.
     *
     * @return string Encoded image data in the target format.
     */
    public function convertFormat(string $contents, string $format, int $quality = 90): string;

    /**
     * Return the pixel dimensions of image data.
     *
     * @param string $contents Encoded image data.
     *
     * @throws MediaException If the source data cannot be decoded.
     *
     * @return array{width: int, height: int}
     */
    public function dimensions(string $contents): array;
}
