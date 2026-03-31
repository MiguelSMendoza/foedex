<?php

declare(strict_types=1);

namespace App\Knowledge\Application;

final readonly class StoredMedia
{
    public function __construct(
        public string $kind,
        public string $originalFilename,
        public string $storedFilename,
        public string $publicPath,
        public ?string $thumbnailPath,
        public string $mimeType,
        public int $size,
        public ?int $width,
        public ?int $height,
    ) {
    }
}
