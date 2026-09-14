<?php

declare(strict_types=1);

namespace EzPhp\Media;

use Imagick;
use ImagickException;

/**
 * Class ImagickDriver
 *
 * ImageTransformerInterface implementation backed by ext-imagick.
 * Supports any format ImageMagick itself supports.
 *
 * @package EzPhp\Media
 */
final class ImagickDriver implements ImageTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public function resize(string $contents, int $width, int $height, bool $preserveAspectRatio = true): string
    {
        $image = $this->decode($contents);

        try {
            $image->resizeImage($width, $height, Imagick::FILTER_LANCZOS, 1, $preserveAspectRatio);

            return $image->getImageBlob();
        } catch (ImagickException $exception) {
            throw new MediaException('Failed to resize image.', 0, $exception);
        } finally {
            $image->destroy();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function crop(string $contents, int $x, int $y, int $width, int $height): string
    {
        $image = $this->decode($contents);

        try {
            $image->cropImage($width, $height, $x, $y);

            return $image->getImageBlob();
        } catch (ImagickException $exception) {
            throw new MediaException('Failed to crop image.', 0, $exception);
        } finally {
            $image->destroy();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function convertFormat(string $contents, string $format, int $quality = 90): string
    {
        $image = $this->decode($contents);

        try {
            $image->setImageFormat(strtoupper($format));
            $image->setImageCompressionQuality($quality);

            return $image->getImageBlob();
        } catch (ImagickException $exception) {
            throw new MediaException("Unsupported image format: {$format}", 0, $exception);
        } finally {
            $image->destroy();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function dimensions(string $contents): array
    {
        $image = $this->decode($contents);

        try {
            return ['width' => $image->getImageWidth(), 'height' => $image->getImageHeight()];
        } finally {
            $image->destroy();
        }
    }

    /**
     * Decode encoded image data into an Imagick handle.
     *
     * @param string $contents Encoded image data.
     *
     * @throws MediaException If the data cannot be decoded.
     *
     * @return Imagick
     */
    private function decode(string $contents): Imagick
    {
        try {
            $image = new Imagick();
            $image->readImageBlob($contents);

            return $image;
        } catch (ImagickException $exception) {
            throw new MediaException('Unable to decode image data.', 0, $exception);
        }
    }
}
