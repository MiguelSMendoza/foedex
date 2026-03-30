<?php

declare(strict_types=1);

namespace App\Identity\Application;

use App\Identity\Domain\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:user:create', description: 'Crea un usuario local para Foedex.')]
final class CreateUserCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED)
            ->addArgument('displayName', InputArgument::REQUIRED)
            ->addArgument('password', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = (string) $input->getArgument('email');
        $displayName = (string) $input->getArgument('displayName');
        $password = (string) $input->getArgument('password');

        $existing = $this->entityManager->getRepository(User::class)->findOneBy(['email' => mb_strtolower($email)]);

        if ($existing instanceof User) {
            $output->writeln('<error>Ya existe un usuario con ese email.</error>');

            return Command::FAILURE;
        }

        $user = (new User())
            ->setEmail($email)
            ->setDisplayName($displayName);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $output->writeln(sprintf('<info>Usuario %s creado correctamente.</info>', $user->getEmail()));

        return Command::SUCCESS;
    }
}
