<?php

declare(strict_types=1);

namespace App\Controller\Api\Auth;

use App\DTO\Response\LoginResponse;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class LoginController extends AbstractController
{
    #[Route(
        path: '/api/login',
        name: 'api_login',
        methods: Request::METHOD_POST,
    )]
    public function index(#[CurrentUser] User $user): JsonResponse
    {
        return $this->json(
            LoginResponse::fromEntity($user),
        );
    }
}
