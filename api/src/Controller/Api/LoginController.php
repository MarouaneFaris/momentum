<?php

namespace App\Controller\Api;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class LoginController extends AbstractController
{
    #[Route(
        path: '/api/login',
        name: 'api_login',
        methods: Request::METHOD_POST,
    )]
    public function index(#[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json([
                'message' => 'bad credentials',
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json('welcome back!');
    }
}
