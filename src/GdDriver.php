<?php

declare(strict_types=1);

namespace EzPhp\Media;

use GdImage;

/**
 * Class GdDriver
 *
 * ImageTransformerInterface implementation backed by ext-gd.
 * Supports JPEG, PNG, WebP, and GIF encoding/decoding.
 *
 * JPEGs are turned upright according to their EXIF Orientation tag on decode,
 * so every operation (and dimensions()) sees the image as it is meant to be
 * viewed; the re-encoded output carries no EXIF, so it cannot be turned twice.
 *
 * @package EzPhp\Media
 */
final class GdDriver implements ImageTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public function resize(string $contents, int $width, int $height, bool $preserveAspectRatio = true): string
    {
        if ($width < 1 || $height < 1) {
            throw new MediaException('Width and height must be positive integers.');
        }

        $image = $this->decode($contents);
        $mime = $this->mimeType($contents);

        $sourceWidth = imagesx($image);
        $sourceHeight = imagesy($image);

        [$targetWidth, $targetHeight] = $preserveAspectRatio
            ? $this->fitDimensions($sourceWidth, $sourceHeight, $width, $height)
            : [$width, $height];

        $resized = imagecreatetruecolor(max(1, $targetWidth), max(1, $targetHeight));
        if ($resized === false) {
            throw new MediaException('Unable to allocate target image canvas.');
        }

        $this->preserveTransparency($resized, $mime);

        imagecopyresampled(
            $resized,
            $image,
            0,
            0,
            0,
            0,
            $targetWidth,
            $targetHeight,
            $sourceWidth,
            $sourceHeight,
        );

        return $this->encode($resized, $mime, 90);
    }

    /**
     * {@inheritdoc}
     */
    public function crop(string $contents, int $x, int $y, int $width, int $height): string
    {
        $image = $this->decode($contents);
        $mime = $this->mimeType($contents);

        $cropped = imagecrop($image, ['x' => $x, 'y' => $y, 'width' => $width, 'height' => $height]);

        if ($cropped === false) {
            throw new MediaException('Failed to crop image.');
        }

        return $this->encode($cropped, $mime, 90);
    }

    /**
     * {@inheritdoc}
     */
    public function convertFormat(string $contents, string $format, int $quality = 90): string
    {
        $image = $this->decode($contents);

        return $this->encode($image, $this->mimeForFormat($format), $quality);
    }

    /**
     * {@inheritdoc}
     */
    public function dimensions(string $contents): array
    {
        $size = getimagesizefromstring($contents);

        if ($size === false) {
            throw new MediaException('Unable to determine image dimensions: invalid image data.');
        }

        // Orientations 5–8 swap width and height once the image is upright.
        return ExifOrientation::fromJpeg($contents) >= 5
            ? ['width' => (int) $size[1], 'height' => (int) $size[0]]
            : ['width' => (int) $size[0], 'height' => (int) $size[1]];
    }

    /**
     * Decode encoded image data into a GdImage handle.
     *
     * @param string $contents Encoded image data.
     *
     * @throws MediaException If the data cannot be decoded.
     *
     * @return GdImage
     */
    private function decode(string $contents): GdImage
    {
        $image = @imagecreatefromstring($contents);

        if ($image === false) {
            throw new MediaException('Unable to decode image data.');
        }

        return $this->orient($image, ExifOrientation::fromJpeg($contents));
    }

    /**
     * Apply an EXIF orientation (2–8) so the image is upright.
     *
     * @param GdImage $image
     * @param int     $orientation
     *
     * @throws MediaException If GD cannot rotate the image.
     *
     * @return GdImage
     */
    private function orient(GdImage $image, int $orientation): GdImage
    {
        if (in_array($orientation, [2, 5, 7], true)) {
            imageflip($image, IMG_FLIP_HORIZONTAL);
        }

        if ($orientation === 4) {
            imageflip($image, IMG_FLIP_VERTICAL);
        }

        // imagerotate() turns counter-clockwise.
        $angle = match ($orientation) {
            3 => 180,
            5, 8 => 90,
            6, 7 => 270,
            default => 0,
        };

        if ($angle === 0) {
            return $image;
        }

        $rotated = imagerotate($image, $angle, 0);

        if ($rotated === false) {
            throw new MediaException('Failed to apply the EXIF orientation.');
        }

        return $rotated;
    }

    /**
     * Determine the MIME type of encoded image data.
     *
     * @param string $contents Encoded image data.
     *
     * @throws MediaException If the MIME type cannot be determined.
     *
     * @return string
     */
    private function mimeType(string $contents): string
    {
        $size = getimagesizefromstring($contents);

        if ($size === false) {
            throw new MediaException('Unable to determine image format.');
        }

        return $size['mime'];
    }

    /**
     * Map a format name to its MIME type.
     *
     * @param string $format Format name: "jpeg", "png", "webp", or "gif".
     *
     * @throws MediaException If the format is unsupported.
     *
     * @return string
     */
    private function mimeForFormat(string $format): string
    {
        return match (strtolower($format)) {
            'jpeg', 'jpg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            default => throw new MediaException("Unsupported image format: {$format}"),
        };
    }

    /**
     * Encode a GdImage handle to bytes in the given MIME type.
     *
     * @param GdImage $image   Decoded image handle.
     * @param string  $mime    Target MIME type.
     * @param int     $quality Compression quality, 0-100 (ignored for png/gif).
     *
     * @throws MediaException If encoding fails or the MIME type is unsupported.
     *
     * @return string
     */
    private function encode(GdImage $image, string $mime, int $quality): string
    {
        ob_start();

        $result = match ($mime) {
            'image/jpeg' => imagejpeg($image, null, $quality),
            'image/png' => imagepng($image, null, (int) round((100 - $quality) * 9 / 100)),
            'image/webp' => imagewebp($image, null, $quality),
            'image/gif' => imagegif($image),
            default => throw new MediaException("Unsupported image format: {$mime}"),
        };

        $data = ob_get_clean();

        if (!$result || $data === false) {
            throw new MediaException('Failed to encode image.');
        }

        return $data;
    }

    /**
     * Fit source dimensions within a bounding box, preserving aspect ratio.
     *
     * @param int $sourceWidth
     * @param int $sourceHeight
     * @param int $boxWidth
     * @param int $boxHeight
     *
     * @return array{0: int, 1: int}
     */
    private function fitDimensions(int $sourceWidth, int $sourceHeight, int $boxWidth, int $boxHeight): array
    {
        $ratio = min($boxWidth / $sourceWidth, $boxHeight / $sourceHeight);

        return [
            max(1, (int) round($sourceWidth * $ratio)),
            max(1, (int) round($sourceHeight * $ratio)),
        ];
    }

    /**
     * Preserve transparency on the target canvas for PNG/WebP/GIF images.
     *
     * @param GdImage $image Target canvas.
     * @param string  $mime  Source MIME type.
     *
     * @return void
     */
    private function preserveTransparency(GdImage $image, string $mime): void
    {
        if (!in_array($mime, ['image/png', 'image/webp', 'image/gif'], true)) {
            return;
        }

        imagealphablending($image, false);
        imagesavealpha($image, true);
        $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
        if ($transparent !== false) {
            imagefilledrectangle($image, 0, 0, imagesx($image), imagesy($image), $transparent);
        }
    }
}
