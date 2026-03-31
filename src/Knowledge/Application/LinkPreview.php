<?php

declare(strict_types=1);

namespace App\Knowledge\Application;

final readonly class LinkPreview
{
    public function __construct(
        public string $url,
        public string $title,
        public ?string $description,
        public ?string $imageUrl,
        public string $platform = 'generic',
        public ?string $embedUrl = null,
    ) {
    }
}
