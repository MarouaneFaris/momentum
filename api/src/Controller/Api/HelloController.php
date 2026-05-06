<?php

declare(strict_types=1);

namespace App\Controller\Api;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class HelloController
{
    #[Route(
        path: '/api/hello',
        methods: Request::METHOD_GET,
    )]
    public function index(): JsonResponse
    {
        return new JsonResponse('Hello World!');
    }
}
