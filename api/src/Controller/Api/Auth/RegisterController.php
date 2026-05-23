<?php

declare(strict_types=1);

namespace App\Controller\Api\Auth;

use App\DTO\RegisterDTO;
use App\Service\RegisterService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class RegisterController extends AbstractController
{
    #[Route(
        path: '/api/register',
        name: 'api_register',
        methods: Request::METHOD_POST,
    )]
    public function index(
        #[MapRequestPayload] RegisterDTO $dto,
        RegisterService $registerService,
    ): JsonResponse {
        $registerService->register($dto);

        return $this->json([
            'message' => 'Registration successful. Please check your email to verify your account.',
        ], Response::HTTP_CREATED);
    }
}
