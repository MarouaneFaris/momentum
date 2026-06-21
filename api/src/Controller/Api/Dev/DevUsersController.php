<?php

declare(strict_types=1);

namespace App\Controller\Api\Dev;

use App\Service\DevService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class DevUsersController extends AbstractController
{
    public function __construct(
        private readonly DevService $devService,
    ) {}

    #[Route(
        path: '/api/dev/users',
        name: 'api_dev_users',
        methods: Request::METHOD_GET,
    )]
    public function __invoke(): JsonResponse
    {
        $this->devService->ensureDevEnvironment();

        return $this->json($this->devService->getUsers());
    }
}
