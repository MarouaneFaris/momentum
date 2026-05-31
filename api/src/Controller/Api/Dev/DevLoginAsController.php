<?php

declare(strict_types=1);

namespace App\Controller\Api\Dev;

use App\DTO\Response\LoginResponse;
use App\Service\AuthTokenManager;
use App\Service\DevService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class DevLoginAsController extends AbstractController
{
    #[Route(
        path: '/api/dev/login-as',
        name: 'api_dev_login_as',
        methods: Request::METHOD_POST,
    )]
    public function __invoke(Request $request, DevService $devService): JsonResponse
    {
        $devService->ensureDevEnvironment();

        $email = $request->toArray()['email'] ?? null;

        if (!$email) {
            return $this->json(['error' => 'email required'], 422);
        }

        $result = $devService->loginAs($email);

        $response = $this->json(LoginResponse::fromEntity($result['user']));
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
