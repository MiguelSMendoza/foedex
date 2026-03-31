<?php

declare(strict_types=1);

namespace App\Identity\UI\Web\Controller;

use App\Identity\Domain\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Shared\UI\Api\ApiViewFactory;

#[IsGranted('ROLE_USER')]
final class ProfileController extends AbstractController
{
    #[Route('/api/profile', name: 'api_profile', methods: ['GET', 'PATCH'])]
    public function __invoke(
        Request $request,
        EntityManagerInterface $entityManager,
        ApiViewFactory $apiViewFactory,
    ): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($request->isMethod('PATCH')) {
            $payload = $request->toArray();
            $user
                ->setDisplayName((string) ($payload['displayName'] ?? $user->getDisplayName()))
                ->setBio(array_key_exists('bio', $payload) ? (string) $payload['bio'] : $user->getBio());
            $entityManager->flush();
        }

        return new JsonResponse([
            'user' => $apiViewFactory->user($user),
        ]);
    }
}
