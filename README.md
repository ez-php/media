# ez-php/media

Image resizing and file processing: transformations over an `ez-php/storage`
`StorageInterface` source, backed by `ext-gd` (default) or `ext-imagick`.

## Usage

```php
use EzPhp\Media\GdDriver;
use EzPhp\Media\ImageProcessor;
use EzPhp\Storage\LocalDriver;

$storage = new LocalDriver('/var/www/html/storage', 'https://example.test/files');
$processor = new ImageProcessor($storage, new GdDriver());

$processor->resize('uploads/photo.jpg', 'uploads/photo-thumb.jpg', 200, 200);
$processor->crop('uploads/photo.jpg', 'uploads/photo-square.jpg', 0, 0, 500, 500);
$processor->convertFormat('uploads/photo.jpg', 'uploads/photo.webp', 'webp');

$processor->dimensions('uploads/photo.jpg'); // ['width' => ..., 'height' => ...]
```

Registering `MediaServiceProvider` in a framework application binds
`ImageTransformerInterface` and `ImageProcessor` into the container (driver
selected from `media.driver` config, `gd` or `imagick`) and wires the `Media`
static façade:

```php
use EzPhp\Media\Media;

Media::resize('uploads/photo.jpg', 'uploads/photo-thumb.jpg', 200, 200);
```

## Drivers

- **`GdDriver`** (default) — `ext-gd`. Supports JPEG, PNG, WebP, and GIF.
  Requires no extra install on most PHP setups.
- **`ImagickDriver`** — `ext-imagick`. Supports any format ImageMagick itself
  supports. Opt in via `media.driver = imagick` or by constructing it directly.

Both implement `ImageTransformerInterface` and operate on raw encoded image
data (bytes in, bytes out) — they know nothing about storage. `ImageProcessor`
is the layer that reads source bytes from a `StorageInterface`, transforms
them, and writes the result back.

## What it does not do

- No thumbnail-set generation, image pipelines, or queued/async processing —
  each call is one synchronous transformation; compose calls or wrap them in
  an `ez-php/queue` job yourself.
- No file validation (MIME type, size, malicious payload sniffing) — that is
  `ez-php/validation`'s job. This package assumes valid image bytes are handed
  to it, and throws `MediaException` if they cannot be decoded.
- No metadata extraction (EXIF, ICC profiles) — out of scope for a first pass.

## Requirements

- PHP `^8.5`
- `ext-gd` (required, `GdDriver`)
- `ext-imagick` (optional, only if using `ImagickDriver`)

## Installation

```bash
composer require ez-php/media
```
