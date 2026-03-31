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
        $client->request('POST', '/api/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'displayName' => 'Miguel',
            'email' => 'miguel@example.com',
            'password' => 'ChangeMe1234',
        ], \JSON_THROW_ON_ERROR));

        self::assertResponseStatusCodeSame(201);

        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => 'miguel@example.com']);

        self::assertInstanceOf(User::class, $user);
        self::assertSame('Miguel', $user->getDisplayName());
    }
}
