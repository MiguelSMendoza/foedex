<?php

declare(strict_types=1);

namespace App\Identity\UI\Web\Controller;

use App\Identity\Domain\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class RegistrationController
{
    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function __invoke(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        ValidatorInterface $validator,
    ): JsonResponse {
        $payload = $request->toArray();

        $email = mb_strtolower(trim((string) ($payload['email'] ?? '')));
        $displayName = trim((string) ($payload['displayName'] ?? ''));
        $plainPassword = (string) ($payload['password'] ?? '');

        if ($email === '' || $displayName === '' || $plainPassword === '') {
            return new JsonResponse(['message' => 'Email, nombre y contraseña son obligatorios.'], 422);
        }

        $user = (new User())
            ->setEmail($email)
            ->setDisplayName($displayName);

        $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));

        $violations = $validator->validate($user);

        if (\count($violations) > 0) {
            return new JsonResponse(['message' => (string) $violations[0]->getMessage()], 422);
        }

        $entityManager->persist($user);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Cuenta creada. Ya puedes iniciar sesión.'], 201);
    }
}
