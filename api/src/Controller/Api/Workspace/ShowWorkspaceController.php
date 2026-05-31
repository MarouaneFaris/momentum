<?php

declare(strict_types=1);

namespace App\Controller\Api\Workspace;

use App\DTO\Response\WorkspaceListItemResponse;
use App\Entity\User;
use App\Entity\Workspace;
use App\Repository\UserWorkspaceRepository;
use App\Security\Voter\WorkspaceVoter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class ShowWorkspaceController extends AbstractController
{
    #[Route(
        path: '/api/workspaces/{id}',
        name: 'api_workspace_show',
        methods: Request::METHOD_GET,
    )]
    #[IsGranted(WorkspaceVoter::VIEW, subject: 'workspace')]
    public function __invoke(
        Workspace $workspace,
        #[CurrentUser] User $user,
        UserWorkspaceRepository $userWorkspaceRepository,
    ): JsonResponse {
        $role = $userWorkspaceRepository->findRoleByUserAndWorkspace($user, $workspace);
        assert($role !== null);

        return $this->json(
            WorkspaceListItemResponse::fromWorkspaceAndRole($workspace, $role),
        );
    }
}
