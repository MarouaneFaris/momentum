<?php

declare(strict_types=1);

namespace App\Controller\Api\Auth;

use App\DTO\Response\LoginResponse;
use App\Entity\User;
use App\Service\AuthTokenManager;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class LoginController extends AbstractController
{
    #[OA\Post(
        path: '/api/login',
        summary: 'Authenticate and receive session cookie',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Login successful — sets auth_token HttpOnly cookie',
                content: new OA\JsonContent(ref: new Model(type: LoginResponse::class))
            ),
            new OA\Response(response: 401, description: 'Invalid credentials'),
            new OA\Response(response: 422, description: 'Validation error — missing or malformed fields'),
        ]
    )]
    #[Route(
        path: '/api/login',
        name: 'api_login',
        methods: Request::METHOD_POST,
    )]
    public function __invoke(
        #[CurrentUser] User $user,
        AuthTokenManager $service,
    ): JsonResponse {
        $result = $service->createToken($user);
        $response = $this->json(LoginResponse::fromEntity($user));
        $response->headers->setCookie(
            Cookie::create(
                name: AuthTokenManager::COOKIE_NAME,
                value: $result['token'],
                expire: $result['expiresAt'],
                secure: true,
                httpOnly: true,
                sameSite: Cookie::SAMESITE_STRICT,
            ),
        );

        return $response;
    }
}
