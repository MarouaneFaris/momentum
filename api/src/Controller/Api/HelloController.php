<?php

declare(stric_types=1);

namespace App\Controller\Api;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class HelloController
{
    #[Route(
        path: '/api/hello',
    )]
    public function index(): JsonResponse
    {
        return new JsonResponse('Hello World!');
    }
}
