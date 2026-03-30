<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Identity\Domain\User;
use Doctrine\ORM\EntityManagerInterface;

final class RegistrationTest extends WebTestCase
{
    public function testUserCanRegister(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/register');

        $form = $crawler->selectButton('Registrarme')->form([
            'registration_form[displayName]' => 'Miguel',
            'registration_form[email]' => 'miguel@example.com',
            'registration_form[plainPassword]' => 'ChangeMe1234',
        ]);

        $client->submit($form);

        self::assertResponseRedirects('/login');

        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => 'miguel@example.com']);

        self::assertInstanceOf(User::class, $user);
        self::assertSame('Miguel', $user->getDisplayName());
    }
}
