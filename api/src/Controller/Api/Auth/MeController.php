<?php

declare(strict_types=1);

namespace App\Controller\Api\Auth;

use App\DTO\Response\LoginResponse;
use App\Entity\User;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class MeController extends AbstractController
{
    #[OA\Get(
        path: '/api/me',
        summary: 'Get the currently authenticated user',
        security: [['cookieAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Authenticated user details',
                content: new OA\JsonContent(ref: new Model(type: LoginResponse::class))
            ),
            new OA\Response(response: 401, description: 'Not authenticated'),
        ]
    )]
    #[Route(
        path: '/api/me',
        name: 'api_me',
        methods: Request::METHOD_GET,
    )]
    public function __invoke(#[CurrentUser] User $user): JsonResponse
    {
        return $this->json(
            LoginResponse::fromEntity($user),
        );
    }
}
