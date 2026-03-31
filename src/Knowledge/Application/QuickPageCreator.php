<?php

declare(strict_types=1);

namespace App\Knowledge\Application;

use App\Identity\Domain\User;
use App\Knowledge\Domain\MediaAsset;
use App\Knowledge\Domain\Page;
use App\Knowledge\UI\Web\Form\PageData;
use App\Shared\Application\PlainTextSanitizer;
use App\Shared\Application\SlugGenerator;
use App\Shared\Application\TitleFormatter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class QuickPageCreator
{
    public function __construct(
        private readonly PageManager $pageManager,
        private readonly LinkPreviewFetcher $linkPreviewFetcher,
        private readonly MediaStorage $mediaStorage,
        private readonly EntityManagerInterface $entityManager,
        private readonly PlainTextSanitizer $plainTextSanitizer,
        private readonly SlugGenerator $slugGenerator,
        private readonly TitleFormatter $titleFormatter,
    ) {
    }

    public function fromText(string $input, User $actor): Page
    {
        $trimmed = trim($input);

        if ($trimmed === '') {
            throw new \InvalidArgumentException('No hay contenido para convertir en pagina.');
        }

        if ($this->looksLikeUrl($trimmed)) {
            return $this->fromLink($trimmed, $actor);
        }

        $clean = $this->plainTextSanitizer->sanitizeBlock($trimmed);
        $rawTitle = $this->titleFromText($clean);
        $title = $this->titleFormatter->truncate($rawTitle);
        $body = $this->removeLeadingTitleLine($clean, $rawTitle);

        $data = new PageData();
        $data->title = $title;
        $data->slug = '';
        $data->excerpt = mb_substr($body !== '' ? $body : $clean, 0, 180);
        $data->markdown = $body !== '' ? $body : $clean;
        $data->changeSummary = 'Creada desde pegado rapido';

        return $this->pageManager->create($data, $actor, 'Capturas');
    }

    public function fromLink(string $url, User $actor): Page
    {
        $preview = $this->linkPreviewFetcher->fetch($url);
        $title = $this->titleFormatter->truncate($preview->title);
        $markdown = $this->buildLinkMarkdown($preview, $title);

        $data = new PageData();
        $data->title = $title;
        $data->slug = '';
        $data->excerpt = $preview->description !== null ? mb_substr($preview->description, 0, 180) : null;
        $data->markdown = $markdown;
        $data->changeSummary = 'Creada desde enlace pegado';

        return $this->pageManager->create($data, $actor, 'Enlaces');
    }

    public function fromUpload(UploadedFile $file, User $actor): Page
    {
        $storedMedia = $this->mediaStorage->store($file);
        $title = $this->plainTextSanitizer->sanitizeInline(pathinfo($storedMedia->originalFilename, \PATHINFO_FILENAME));
        $title = $this->titleFormatter->truncate($title !== '' ? $title : 'Archivo compartido');

        $data = new PageData();
        $data->title = $title;
        $data->slug = '';
        $data->changeSummary = 'Creada desde carga rapida';

        if ($storedMedia->kind === 'image') {
            $data->excerpt = 'Imagen subida desde la portada';
            $data->markdown = sprintf(
                "![%s](%s)\n\n[Descargar imagen original](%s)",
                $title,
                $storedMedia->publicPath,
                $storedMedia->publicPath,
            );
            $page = $this->pageManager->create($data, $actor, 'Imagenes');
        } else {
            $data->excerpt = 'Archivo subido desde la portada';
            $data->markdown = sprintf(
                "[Descargar archivo original](%s)",
                $storedMedia->publicPath,
            );
            $page = $this->pageManager->create($data, $actor, 'Archivos');
        }

        $asset = (new MediaAsset())
            ->setPage($page)
            ->setKind($storedMedia->kind)
            ->setOriginalFilename($storedMedia->originalFilename)
            ->setStoredFilename($storedMedia->storedFilename)
            ->setPublicPath($storedMedia->publicPath)
            ->setThumbnailPath($storedMedia->thumbnailPath)
            ->setMimeType($storedMedia->mimeType)
            ->setSize($storedMedia->size)
            ->setWidth($storedMedia->width)
            ->setHeight($storedMedia->height)
            ->setCreatedBy($actor);

        $page->addMediaAsset($asset);
        $this->entityManager->persist($asset);
        $this->entityManager->flush();

        return $page;
    }

    private function looksLikeUrl(string $value): bool
    {
        return preg_match('#^https?://#i', $value) === 1;
    }

    private function titleFromText(string $value): string
    {
        $lines = preg_split("/\n+/", $value) ?: [];

        foreach ($lines as $line) {
            $candidate = $this->plainTextSanitizer->sanitizeInline($line);

            if ($candidate !== '') {
                return mb_substr($candidate, 0, 120);
            }
        }

        return 'Nota rapida';
    }

    private function removeLeadingTitleLine(string $markdown, string $rawTitle): string
    {
        $lines = preg_split("/\n/", $markdown) ?: [];
        $titleLine = trim($rawTitle);

        while ($lines !== [] && trim((string) $lines[0]) === '') {
            array_shift($lines);
        }

        if ($lines !== [] && $this->plainTextSanitizer->sanitizeInline((string) $lines[0]) === $titleLine) {
            array_shift($lines);
        }

        $body = trim(implode("\n", $lines));

        return $body;
    }

    private function buildLinkMarkdown(LinkPreview $preview, string $title): string
    {
        return match ($preview->platform) {
            'instagram' => $this->buildInstagramMarkdown($preview, $title),
            'x' => $this->buildXMarkdown($preview),
            'youtube' => $this->buildYouTubeMarkdown($preview),
            default => $this->buildGenericLinkMarkdown($preview, $title),
        };
    }

    private function buildInstagramMarkdown(LinkPreview $preview, string $title): string
    {
        $blocks = [];

        if ($preview->imageUrl !== null) {
            $blocks[] = sprintf('[![%s](%s)](%s)', $title, $preview->imageUrl, $preview->url);
        }

        if ($preview->description !== null && $preview->description !== '') {
            $blocks[] = $preview->description;
        }

        if ($preview->imageUrl === null) {
            $blocks[] = sprintf('[Abrir publicación original](%s)', $preview->url);
        }

        return implode("\n\n", $blocks);
    }

    private function buildXMarkdown(LinkPreview $preview): string
    {
        $blocks = [];

        if ($preview->description !== null && $preview->description !== '') {
            $blocks[] = $preview->description;
        }

        $blocks[] = sprintf('[Abrir publicación en X](%s)', $preview->url);

        return implode("\n\n", $blocks);
    }

    private function buildGenericLinkMarkdown(LinkPreview $preview, string $title): string
    {
        $blocks = [];

        if ($preview->description !== null && $preview->description !== '') {
            $blocks[] = $preview->description;
        }

        if ($preview->imageUrl !== null) {
            $blocks[] = sprintf('![%s](%s)', $title, $preview->imageUrl);
        }

        $blocks[] = sprintf('[Visitar enlace original](%s)', $preview->url);

        return implode("\n\n", $blocks);
    }

    private function buildYouTubeMarkdown(LinkPreview $preview): string
    {
        $blocks = [];

        if ($preview->title !== '') {
            $blocks[] = sprintf('## %s', $preview->title);
        }

        if ($preview->embedUrl !== null) {
            $blocks[] = sprintf('[[youtube:%s]]', $preview->embedUrl);
        }

        if ($preview->description !== null && $preview->description !== '' && $preview->description !== $preview->title) {
            $blocks[] = $preview->description;
        }

        $blocks[] = sprintf('[Abrir vídeo en YouTube](%s)', $preview->url);

        return implode("\n\n", $blocks);
    }
}
