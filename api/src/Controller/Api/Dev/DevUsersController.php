<?php

declare(strict_types=1);

namespace App\Controller\Api\Dev;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class DevUsersController extends AbstractController
{
    public function __construct(#[Autowire('%kernel.environment%')] private string $appEnv) {}

    #[Route(
        path: '/api/dev/users',
        name: 'api_dev_users',
        methods: Request::METHOD_GET,
    )]
    public function index(UserRepository $userRepository): JsonResponse
    {
        if ($this->appEnv !== 'dev') {
            throw new NotFoundHttpException();
        }

        $users = $userRepository->findAll();

        return $this->json(array_map(
            fn ($user) => ['id' => (string) $user->getId(), 'email' => $user->getEmail()],
            $users,
        ));
    }
}
