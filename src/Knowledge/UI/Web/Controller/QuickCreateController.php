<?php

declare(strict_types=1);

namespace App\Knowledge\UI\Web\Controller;

use App\Identity\Domain\User;
use App\Knowledge\Application\QuickPageCreator;
use App\Shared\UI\Api\ApiViewFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class QuickCreateController extends AbstractController
{
    #[Route('/api/quick-create/text', name: 'api_quick_create_text', methods: ['POST'])]
    public function text(Request $request, QuickPageCreator $quickPageCreator, ApiViewFactory $apiViewFactory): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $payload = $request->toArray();
        $input = trim((string) ($payload['input'] ?? $payload['text'] ?? ''));

        if ($input === '') {
            return new JsonResponse(['message' => 'Pega un enlace o texto para crear la pagina.'], 422);
        }

        try {
            $page = $quickPageCreator->fromText($input, $user);
        } catch (\InvalidArgumentException $exception) {
            return new JsonResponse(['message' => $exception->getMessage()], 422);
        }

        return new JsonResponse([
            'message' => 'Pagina creada automaticamente.',
            'page' => $apiViewFactory->page($page),
        ], Response::HTTP_CREATED);
    }

    #[Route('/api/quick-create/upload', name: 'api_quick_create_upload', methods: ['POST'])]
    public function upload(Request $request, QuickPageCreator $quickPageCreator, ApiViewFactory $apiViewFactory): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $file = $request->files->get('file');

        if (!$file instanceof \Symfony\Component\HttpFoundation\File\UploadedFile) {
            return new JsonResponse(['message' => 'No se ha recibido ningun fichero.'], 422);
        }

        try {
            $page = $quickPageCreator->fromUpload($file, $user);
        } catch (\InvalidArgumentException $exception) {
            return new JsonResponse(['message' => $exception->getMessage()], 422);
        }

        return new JsonResponse([
            'message' => 'Pagina creada a partir del fichero.',
            'page' => $apiViewFactory->page($page),
        ], Response::HTTP_CREATED);
    }
}
