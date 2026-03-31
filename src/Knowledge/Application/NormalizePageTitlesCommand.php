<?php

declare(strict_types=1);

namespace App\Knowledge\Application;

use App\Knowledge\Domain\Page;
use App\Shared\Application\TitleFormatter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:pages:normalize-titles', description: 'Trunca títulos de páginas y revisiones al máximo permitido.')]
final class NormalizePageTitlesCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TitleFormatter $titleFormatter,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var list<Page> $pages */
        $pages = $this->entityManager->getRepository(Page::class)->findAll();
        $changes = 0;

        foreach ($pages as $page) {
            $currentTitle = $page->getCurrentTitle();
            $normalizedTitle = $this->titleFormatter->truncate($currentTitle);

            if ($normalizedTitle !== $currentTitle) {
                $page->setCurrentTitle($normalizedTitle);
                ++$changes;
            }

            foreach ($page->getRevisions() as $revision) {
                $revisionTitle = $revision->getTitleSnapshot();
                $normalizedRevisionTitle = $this->titleFormatter->truncate($revisionTitle);

                if ($normalizedRevisionTitle !== $revisionTitle) {
                    $revision->setTitleSnapshot($normalizedRevisionTitle);
                    ++$changes;
                }
            }
        }

        $this->entityManager->flush();

        $output->writeln(sprintf('<info>%d títulos normalizados.</info>', $changes));

        return Command::SUCCESS;
    }
}
