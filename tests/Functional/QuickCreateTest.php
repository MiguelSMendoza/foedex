<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Identity\Domain\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class QuickCreateTest extends WebTestCase
{
    public function testAuthenticatedUserCanCreateQuickPageFromText(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $client->loginUser($user);

        $client->request('POST', '/api/quick-create/text', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'input' => "Una nota importante\n\nCon contexto rapido para el equipo.",
        ], \JSON_THROW_ON_ERROR));

        self::assertResponseStatusCodeSame(201);

        $payload = json_decode((string) $client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        self::assertSame('Una nota importante', $payload['page']['title']);
        self::assertMatchesRegularExpression('/^[a-z0-9]{12}$/', $payload['page']['slug']);
        self::assertStringNotContainsString('<p>Una nota importante</p>', $payload['page']['html']);
        self::assertStringContainsString('<p>Con contexto rapido para el equipo.</p>', $payload['page']['html']);
    }

    public function testAuthenticatedUserCanCreateQuickPageFromUploadedImage(): void
    {
        $client = static::createClient();
        $user = $this->createUser('image@example.com');
        $client->loginUser($user);

        $path = sys_get_temp_dir().'/foedex-test-image.png';
        $image = imagecreatetruecolor(20, 20);
        $color = imagecolorallocate($image, 30, 180, 120);
        imagefill($image, 0, 0, $color);
        imagepng($image, $path);

        $uploadedFile = new UploadedFile($path, 'captura.png', 'image/png', null, true);

        $client->request('POST', '/api/quick-create/upload', [], [
            'file' => $uploadedFile,
        ]);

        self::assertResponseStatusCodeSame(201);

        $payload = json_decode((string) $client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        self::assertSame('captura', $payload['page']['title']);
        self::assertMatchesRegularExpression('/^[a-z0-9]{12}$/', $payload['page']['slug']);
        self::assertNotEmpty($payload['page']['media']);
        self::assertSame('image', $payload['page']['media'][0]['kind']);
        self::assertNotNull($payload['page']['media'][0]['thumbnailUrl']);
        self::assertStringContainsString('<img', $payload['page']['html']);
        self::assertStringContainsString('src="/uploads/originals/', $payload['page']['html']);
        self::assertStringContainsString('href="/uploads/originals/', $payload['page']['html']);

        @unlink($path);
    }

    public function testDuplicateUploadedImageTitlesProduceUniqueSlugs(): void
    {
        $client = static::createClient();
        $user = $this->createUser('duplicate@example.com');
        $client->loginUser($user);

        $firstPath = sys_get_temp_dir().'/foedex-duplicate-image-1.png';
        $secondPath = sys_get_temp_dir().'/foedex-duplicate-image-2.png';
        $image = imagecreatetruecolor(20, 20);
        $color = imagecolorallocate($image, 160, 90, 40);
        imagefill($image, 0, 0, $color);
        imagepng($image, $firstPath);
        imagepng($image, $secondPath);

        $first = new UploadedFile($firstPath, 'captura.png', 'image/png', null, true);
        $client->request('POST', '/api/quick-create/upload', [], [
            'file' => $first,
        ]);
        self::assertResponseStatusCodeSame(201);
        $firstPayload = json_decode((string) $client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        $second = new UploadedFile($secondPath, 'captura.png', 'image/png', null, true);
        $client->request('POST', '/api/quick-create/upload', [], [
            'file' => $second,
        ]);
        self::assertResponseStatusCodeSame(201);
        $secondPayload = json_decode((string) $client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        self::assertMatchesRegularExpression('/^[a-z0-9]{12}$/', $firstPayload['page']['slug']);
        self::assertMatchesRegularExpression('/^[a-z0-9]{12}$/', $secondPayload['page']['slug']);
        self::assertNotSame($firstPayload['page']['slug'], $secondPayload['page']['slug']);

        @unlink($firstPath);
        @unlink($secondPath);
    }

    public function testAuthenticatedUserCanCreateQuickPageFromUploadedZip(): void
    {
        $client = static::createClient();
        $user = $this->createUser('zip@example.com');
        $client->loginUser($user);

        $path = sys_get_temp_dir().'/foedex-test-archive.zip';
        $zip = new \ZipArchive();
        self::assertTrue($zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE));
        $zip->addFromString('readme.txt', 'contenido de prueba');
        $zip->close();

        $uploadedFile = new UploadedFile($path, 'archivo-demo.zip', 'application/zip', null, true);

        $client->request('POST', '/api/quick-create/upload', [], [
            'file' => $uploadedFile,
        ]);

        self::assertResponseStatusCodeSame(201);

        $payload = json_decode((string) $client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        self::assertSame('archivo-demo', $payload['page']['title']);
        self::assertMatchesRegularExpression('/^[a-z0-9]{12}$/', $payload['page']['slug']);
        self::assertNotEmpty($payload['page']['media']);
        self::assertSame('file', $payload['page']['media'][0]['kind']);
        self::assertNull($payload['page']['media'][0]['thumbnailUrl']);
        self::assertStringContainsString('Descargar archivo original', $payload['page']['html']);

        @unlink($path);
    }

    public function testUnauthenticatedQuickCreateUploadReturnsJsonUnauthorized(): void
    {
        $client = static::createClient();

        $path = sys_get_temp_dir().'/foedex-test-archive-anon.zip';
        $zip = new \ZipArchive();
        self::assertTrue($zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE));
        $zip->addFromString('readme.txt', 'contenido de prueba');
        $zip->close();

        $uploadedFile = new UploadedFile($path, 'archivo-demo.zip', 'application/zip', null, true);

        $client->request('POST', '/api/quick-create/upload', [], [
            'file' => $uploadedFile,
        ]);

        self::assertResponseStatusCodeSame(401);
        self::assertStringStartsWith('application/json', (string) $client->getResponse()->headers->get('content-type'));

        $payload = json_decode((string) $client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertSame('Necesitas iniciar sesión para realizar esta acción.', $payload['message']);

        @unlink($path);
    }

    private function createUser(string $email = 'quick@example.com'): User
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        /** @var UserPasswordHasherInterface $passwordHasher */
        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $user = (new User())
            ->setEmail($email)
            ->setDisplayName('Quick User');
        $user->setPassword($passwordHasher->hashPassword($user, 'ChangeMe1234'));

        $entityManager->persist($user);
        $entityManager->flush();

        return $user;
    }
}
