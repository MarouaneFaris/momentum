<?php

declare(strict_types=1);

namespace App\Controller\Api\Auth;

use App\DTO\RegisterDTO;
use App\Service\RegisterService;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class RegisterController extends AbstractController
{
    #[OA\Post(
        path: '/api/register',
        summary: 'Register a new user account',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: new Model(type: RegisterDTO::class))
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Registration successful',
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: 'message', type: 'string')],
                    type: 'object'
                )
            ),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    #[Route(
        path: '/api/register',
        name: 'api_register',
        methods: Request::METHOD_POST,
    )]
    public function __invoke(
        #[MapRequestPayload] RegisterDTO $dto,
        RegisterService $registerService,
    ): JsonResponse {
        $registerService->register($dto);

        return $this->json([
            'message' => 'Registration successful. Please check your email to verify your account.',
        ], Response::HTTP_CREATED);
    }
}
