<?php

declare(strict_types=1);

namespace App\Controller\Api\Dev;

use App\DTO\Response\LoginResponse;
use App\Repository\UserRepository;
use App\Service\AuthTokenManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class DevLoginAsController extends AbstractController
{
    public function __construct(#[Autowire('%kernel.environment%')] private string $appEnv) {}

    #[Route(
        path: '/api/dev/login-as',
        name: 'api_dev_login_as',
        methods: Request::METHOD_POST,
    )]
    public function index(Request $request, UserRepository $userRepository, AuthTokenManager $tokenManager): JsonResponse
    {
        if ($this->appEnv !== 'dev') {
            throw new NotFoundHttpException();
        }

        $data = $request->toArray();
        $email = $data['email'] ?? null;

        if (!$email) {
            return $this->json(['error' => 'email required'], 422);
        }

        $user = $userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            return $this->json(['error' => 'user not found'], 404);
        }

        $result = $tokenManager->createToken($user);

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
