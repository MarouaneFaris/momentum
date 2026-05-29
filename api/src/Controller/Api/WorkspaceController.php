<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\DTO\Response\WorkspaceListItemResponse;
use App\Entity\User;
use App\Repository\WorkspaceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class WorkspaceController extends AbstractController
{
    #[Route(
        path: '/api/workspaces',
        name: 'api_workspaces_list',
        methods: Request::METHOD_GET,
    )]
    public function list(
        #[CurrentUser] User $user,
        WorkspaceRepository $workspaceRepository,
    ): JsonResponse {
        $memberships = $workspaceRepository->findByUser($user);

        return $this->json(
            array_map(
                static fn(array $m) => WorkspaceListItemResponse::fromWorkspaceAndRole($m['workspace'], $m['role']),
                $memberships,
            ),
        );
    }
}
