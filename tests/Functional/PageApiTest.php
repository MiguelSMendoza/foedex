<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Identity\Domain\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class PageApiTest extends WebTestCase
{
    public function testAuthenticatedUserCanCreatePageAndReadRenderedHtml(): void
    {
        $client = static::createClient();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        /** @var UserPasswordHasherInterface $passwordHasher */
        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $user = (new User())
            ->setEmail('editor@example.com')
            ->setDisplayName('Editor');
        $user->setPassword($passwordHasher->hashPassword($user, 'ChangeMe1234'));

        $entityManager->persist($user);
        $entityManager->flush();

        $client->loginUser($user);
        $client->request('POST', '/api/pages', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'title' => 'Guia React Symfony',
            'slug' => 'guia-react-symfony',
            'excerpt' => 'Resumen editorial',
            'markdown' => "# Hola\n\nEste contenido se renderiza como HTML y no como markdown crudo.",
            'changeSummary' => 'Primera version',
            'newCategories' => 'React, Symfony',
        ], \JSON_THROW_ON_ERROR));

        self::assertResponseStatusCodeSame(201);

        $payload = json_decode((string) $client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        self::assertSame('Guia React Symfony', $payload['page']['title']);
        self::assertStringContainsString('<h1>', $payload['page']['html']);
        self::assertStringNotContainsString('# Hola', $payload['page']['html']);
    }

    public function testUpdatingPageWithEmptySlugKeepsCurrentSlug(): void
    {
        $client = static::createClient();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        /** @var UserPasswordHasherInterface $passwordHasher */
        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $user = (new User())
            ->setEmail('keeper@example.com')
            ->setDisplayName('Slug Keeper');
        $user->setPassword($passwordHasher->hashPassword($user, 'ChangeMe1234'));

        $entityManager->persist($user);
        $entityManager->flush();

        $client->loginUser($user);
        $client->request('POST', '/api/pages', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'title' => 'Documento estable',
            'slug' => 'documento-estable',
            'excerpt' => 'Resumen estable',
            'markdown' => "# Documento estable\n\nTexto suficiente para guardar la pagina sin problemas.",
            'changeSummary' => 'Primera version',
            'newCategories' => 'Documentos',
        ], \JSON_THROW_ON_ERROR));

        self::assertResponseStatusCodeSame(201);

        $client->request('PUT', '/api/pages/documento-estable', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'title' => 'Documento estable actualizado',
            'slug' => '',
            'excerpt' => 'Resumen actualizado',
            'markdown' => "# Documento estable actualizado\n\nTexto suficiente para guardar otra revision sin cambiar la URL.",
            'changeSummary' => 'Segunda version',
            'categories' => [],
            'newCategories' => 'Documentos',
        ], \JSON_THROW_ON_ERROR));

        self::assertResponseStatusCodeSame(200);

        $payload = json_decode((string) $client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        self::assertSame('documento-estable', $payload['page']['slug']);
        self::assertSame('Documento estable actualizado', $payload['page']['title']);
    }

    public function testCreatingPageWithTooLongTitleReturnsValidationError(): void
    {
        $client = static::createClient();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        /** @var UserPasswordHasherInterface $passwordHasher */
        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $user = (new User())
            ->setEmail('validator@example.com')
            ->setDisplayName('Validator');
        $user->setPassword($passwordHasher->hashPassword($user, 'ChangeMe1234'));

        $entityManager->persist($user);
        $entityManager->flush();

        $client->loginUser($user);
        $client->request('POST', '/api/pages', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'title' => str_repeat('a', 61),
            'slug' => '',
            'excerpt' => 'Resumen',
            'markdown' => "# Titulo largo\n\nContenido suficientemente largo para que el error venga del titulo y no del markdown.",
            'changeSummary' => 'Prueba de validacion',
            'newCategories' => 'Pruebas',
        ], \JSON_THROW_ON_ERROR));

        self::assertResponseStatusCodeSame(422);

        $payload = json_decode((string) $client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        self::assertContains('El título no puede superar los 60 caracteres.', $payload['errors']);
    }

    public function testCreatorCanArchiveOwnPageAndItStopsAppearingInReadEndpoints(): void
    {
        $client = static::createClient();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        /** @var UserPasswordHasherInterface $passwordHasher */
        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $user = (new User())
            ->setEmail('owner@example.com')
            ->setDisplayName('Owner');
        $user->setPassword($passwordHasher->hashPassword($user, 'ChangeMe1234'));

        $entityManager->persist($user);
        $entityManager->flush();

        $client->loginUser($user);
        $client->request('POST', '/api/pages', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'title' => 'Pagina propia borrable',
            'slug' => 'pagina-propia-borrable',
            'excerpt' => 'Resumen breve',
            'markdown' => "# Pagina propia borrable\n\nContenido suficientemente largo para poder archivarla despues.",
            'changeSummary' => 'Primera version',
            'newCategories' => 'Pruebas',
        ], \JSON_THROW_ON_ERROR));

        self::assertResponseStatusCodeSame(201);

        $client->request('DELETE', '/api/pages/pagina-propia-borrable');
        self::assertResponseStatusCodeSame(200);

        $payload = json_decode((string) $client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertSame('Página archivada.', $payload['message']);

        $client->request('GET', '/api/pages/pagina-propia-borrable');
        self::assertResponseStatusCodeSame(404);

        $client->request('GET', '/api/home');
        self::assertResponseIsSuccessful();

        $homePayload = json_decode((string) $client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertSame([], $homePayload['pages']);
    }

    public function testUserCannotArchivePageCreatedByAnotherUser(): void
    {
        $client = static::createClient();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        /** @var UserPasswordHasherInterface $passwordHasher */
        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $owner = (new User())
            ->setEmail('page-owner@example.com')
            ->setDisplayName('Page Owner');
        $owner->setPassword($passwordHasher->hashPassword($owner, 'ChangeMe1234'));

        $otherUser = (new User())
            ->setEmail('other-user@example.com')
            ->setDisplayName('Other User');
        $otherUser->setPassword($passwordHasher->hashPassword($otherUser, 'ChangeMe1234'));

        $entityManager->persist($owner);
        $entityManager->persist($otherUser);
        $entityManager->flush();

        $client->loginUser($owner);
        $client->request('POST', '/api/pages', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'title' => 'Pagina ajena',
            'slug' => 'pagina-ajena',
            'excerpt' => 'Resumen breve',
            'markdown' => "# Pagina ajena\n\nContenido suficientemente largo para probar permisos de borrado.",
            'changeSummary' => 'Primera version',
            'newCategories' => 'Pruebas',
        ], \JSON_THROW_ON_ERROR));

        self::assertResponseStatusCodeSame(201);

        $client->loginUser($otherUser);
        $client->request('DELETE', '/api/pages/pagina-ajena');
        self::assertResponseStatusCodeSame(403);

        $payload = json_decode((string) $client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertSame('Solo puedes borrar páginas creadas por ti.', $payload['message']);

        $client->request('GET', '/api/pages/pagina-ajena');
        self::assertResponseIsSuccessful();
    }
}
