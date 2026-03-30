<?php

declare(strict_types=1);

namespace App\Knowledge\Application;

use App\Knowledge\Domain\Page;
use App\Shared\Infrastructure\Markdown\MarkdownRenderer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:pages:rebuild-html', description: 'Recalcula el HTML sanitizado de todas las páginas.')]
final class RebuildPageHtmlCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MarkdownRenderer $markdownRenderer,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $pages = $this->entityManager->getRepository(Page::class)->findAll();

        foreach ($pages as $page) {
            $page->setCurrentHtml($this->markdownRenderer->toSanitizedHtml($page->getCurrentMarkdown()));
        }

        $this->entityManager->flush();

        $output->writeln(sprintf('<info>%d páginas actualizadas.</info>', \count($pages)));

        return Command::SUCCESS;
    }
}
