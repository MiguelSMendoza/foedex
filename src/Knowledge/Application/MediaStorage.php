<?php

declare(strict_types=1);

namespace App\Knowledge\Application;

use App\Shared\Application\SlugGenerator;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class MediaStorage
{
    private const IMAGE_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
    ];

    private const FILE_MIME_TYPES = [
        'application/pdf',
        'text/plain',
        'text/markdown',
        'text/csv',
        'application/zip',
        'application/x-zip-compressed',
    ];

    public function __construct(
        private readonly string $projectDir,
        private readonly SlugGenerator $slugGenerator,
    ) {
    }

    public function store(UploadedFile $file): StoredMedia
    {
        if (!$file->isValid()) {
            throw new \InvalidArgumentException('El fichero subido no es valido.');
        }

        $mimeType = (string) $file->getMimeType();
        $size = (int) $file->getSize();

        if ($size <= 0 || $size > 15 * 1024 * 1024) {
            throw new \InvalidArgumentException('El fichero supera el tamano maximo permitido.');
        }

        $isImage = \in_array($mimeType, self::IMAGE_MIME_TYPES, true);
        $isFile = \in_array($mimeType, self::FILE_MIME_TYPES, true);

        if (!$isImage && !$isFile) {
            throw new \InvalidArgumentException('Tipo de fichero no permitido.');
        }

        $uploadsDir = $this->projectDir.'/public/uploads/originals';
        $thumbsDir = $this->projectDir.'/public/uploads/thumbnails';
        $this->ensureDirectory($uploadsDir);
        $this->ensureDirectory($thumbsDir);

        $originalName = $file->getClientOriginalName() !== '' ? $file->getClientOriginalName() : 'archivo';
        $baseName = $this->slugGenerator->slugify(pathinfo($originalName, \PATHINFO_FILENAME));
        $extension = $file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'bin';
        $storedFilename = sprintf('%s-%s.%s', $baseName, bin2hex(random_bytes(6)), mb_strtolower($extension));

        $file->move($uploadsDir, $storedFilename);
        $publicPath = '/uploads/originals/'.$storedFilename;

        if (!$isImage) {
            return new StoredMedia(
                kind: 'file',
                originalFilename: $originalName,
                storedFilename: $storedFilename,
                publicPath: $publicPath,
                thumbnailPath: null,
                mimeType: $mimeType,
                size: $size,
                width: null,
                height: null,
            );
        }

        $absolutePath = $uploadsDir.'/'.$storedFilename;
        [$width, $height] = getimagesize($absolutePath) ?: [null, null];
        $thumbFilename = sprintf('%s-thumb-%s.jpg', $baseName, bin2hex(random_bytes(4)));
        $thumbAbsolutePath = $thumbsDir.'/'.$thumbFilename;
        $this->createThumbnail($absolutePath, $mimeType, $thumbAbsolutePath);

        return new StoredMedia(
            kind: 'image',
            originalFilename: $originalName,
            storedFilename: $storedFilename,
            publicPath: $publicPath,
            thumbnailPath: '/uploads/thumbnails/'.$thumbFilename,
            mimeType: $mimeType,
            size: $size,
            width: $width ?: null,
            height: $height ?: null,
        );
    }

    private function createThumbnail(string $sourcePath, string $mimeType, string $targetPath): void
    {
        $source = match ($mimeType) {
            'image/jpeg' => imagecreatefromjpeg($sourcePath),
            'image/png' => imagecreatefrompng($sourcePath),
            'image/gif' => imagecreatefromgif($sourcePath),
            'image/webp' => imagecreatefromwebp($sourcePath),
            default => false,
        };

        if ($source === false) {
            throw new \InvalidArgumentException('No se ha podido procesar la imagen.');
        }

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $maxWidth = 480;
        $maxHeight = 360;
        $ratio = min($maxWidth / $sourceWidth, $maxHeight / $sourceHeight, 1);
        $targetWidth = max(1, (int) floor($sourceWidth * $ratio));
        $targetHeight = max(1, (int) floor($sourceHeight * $ratio));

        $thumbnail = imagecreatetruecolor($targetWidth, $targetHeight);
        $background = imagecolorallocate($thumbnail, 255, 255, 255);
        imagefill($thumbnail, 0, 0, $background);
        imagecopyresampled($thumbnail, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight);
        imagejpeg($thumbnail, $targetPath, 85);
    }

    private function ensureDirectory(string $directory): void
    {
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new \RuntimeException(sprintf('No se ha podido crear el directorio "%s".', $directory));
        }
    }
}
