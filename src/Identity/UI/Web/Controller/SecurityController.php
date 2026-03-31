<?php

declare(strict_types=1);

namespace App\Identity\UI\Web\Controller;

use App\Identity\Domain\User;
use App\Shared\UI\Api\ApiViewFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class SecurityController extends AbstractController
{
    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(ApiViewFactory $apiViewFactory): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();

        if ($user instanceof User) {
            return new JsonResponse([
                'message' => 'Sesion iniciada correctamente.',
                'user' => $apiViewFactory->user($user),
            ]);
        }

        return new JsonResponse([
            'message' => 'Credenciales no validas.',
        ], Response::HTTP_UNAUTHORIZED);
    }

    #[Route('/api/logout', name: 'api_logout', methods: ['POST'])]
    public function logout(): JsonResponse
    {
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/session', name: 'api_session', methods: ['GET'])]
    public function session(ApiViewFactory $apiViewFactory): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();

        return new JsonResponse([
            'user' => $user instanceof User ? $apiViewFactory->user($user) : null,
        ]);
    }
}
