<?php

declare(strict_types=1);

namespace App\Controller\Api\Workspace;

use App\DTO\Response\WorkspaceListItemResponse;
use App\Entity\User;
use App\Entity\UserWorkspace;
use App\Repository\UserWorkspaceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class ListWorkspacesController extends AbstractController
{
    #[Route(
        path: '/api/workspaces',
        name: 'api_workspaces_list',
        methods: Request::METHOD_GET,
    )]
    public function __invoke(
        #[CurrentUser] User $user,
        UserWorkspaceRepository $userWorkspaceRepository,
    ): JsonResponse {
        $userWorkspaces = $userWorkspaceRepository->findByUser($user);

        return $this->json(
            array_map(
                static fn (UserWorkspace $uw) => WorkspaceListItemResponse::fromWorkspaceAndRole($uw->getWorkspace(), $uw->getRole()),
                $userWorkspaces,
            ),
        );
    }
}
