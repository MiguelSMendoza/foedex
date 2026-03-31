<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Identity\Domain\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class LoginTest extends WebTestCase
{
    public function testJsonLoginReturnsSessionPayload(): void
    {
        $client = static::createClient();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        /** @var UserPasswordHasherInterface $passwordHasher */
        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $user = (new User())
            ->setEmail('login@example.com')
            ->setDisplayName('Login User');
        $user->setPassword($passwordHasher->hashPassword($user, 'ChangeMe1234'));

        $entityManager->persist($user);
        $entityManager->flush();

        $client->request('POST', '/api/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'login@example.com',
            'password' => 'ChangeMe1234',
        ], \JSON_THROW_ON_ERROR));

        self::assertResponseStatusCodeSame(200);

        $payload = json_decode((string) $client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        self::assertSame('Sesion iniciada correctamente.', $payload['message']);
        self::assertSame('login@example.com', $payload['user']['email']);
    }

    public function testApiLogoutReturnsNoContentAndClearsSession(): void
    {
        $client = static::createClient();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        /** @var UserPasswordHasherInterface $passwordHasher */
        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $user = (new User())
            ->setEmail('logout@example.com')
            ->setDisplayName('Logout User');
        $user->setPassword($passwordHasher->hashPassword($user, 'ChangeMe1234'));

        $entityManager->persist($user);
        $entityManager->flush();

        $client->loginUser($user);
        $client->request('POST', '/api/logout');

        self::assertResponseStatusCodeSame(204);

        $client->request('GET', '/api/session');
        self::assertResponseIsSuccessful();

        $payload = json_decode((string) $client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        self::assertNull($payload['user']);
    }
}
