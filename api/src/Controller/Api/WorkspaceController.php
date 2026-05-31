<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\DTO\CreateWorkspaceDTO;
use App\DTO\Response\WorkspaceListItemResponse;
use App\Entity\User;
use App\Entity\UserWorkspace;
use App\Entity\Workspace;
use App\Enum\WorkspaceRole;
use App\Repository\UserWorkspaceRepository;
use App\Security\Voter\WorkspaceVoter;
use App\Service\WorkspaceService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class WorkspaceController extends AbstractController
{
    #[Route(
        path: '/api/workspaces',
        name: 'api_workspaces_list',
        methods: Request::METHOD_GET,
    )]
    public function list(
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

    #[Route(
        path: '/api/workspaces',
        name: 'api_workspaces_create',
        methods: Request::METHOD_POST,
    )]
    public function create(
        #[MapRequestPayload] CreateWorkspaceDTO $dto,
        #[CurrentUser] User $user,
        WorkspaceService $service,
    ): JsonResponse {
        $workspace = $service->create($dto->name, $user);

        return $this->json(
            WorkspaceListItemResponse::fromWorkspaceAndRole($workspace, WorkspaceRole::Owner),
            Response::HTTP_CREATED,
        );
    }

    #[Route(
        path: '/api/workspaces/{id}',
        name: 'api_workspace_show',
        methods: Request::METHOD_GET,
    )]
    #[IsGranted(WorkspaceVoter::VIEW, subject: 'workspace')]
    public function show(
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

    #[Route(
        path: '/api/workspaces/{id}',
        name: 'api_workspace_update',
        methods: Request::METHOD_PATCH,
    )]
    #[IsGranted(WorkspaceVoter::EDIT, subject: 'workspace')]
    public function update(
        Workspace $workspace,
        #[MapRequestPayload] CreateWorkspaceDTO $dto,
        WorkspaceService $service,
    ): JsonResponse {
        $service->rename($workspace, $dto->name);

        return $this->json(
            WorkspaceListItemResponse::fromWorkspaceAndRole($workspace, WorkspaceRole::Owner),
        );
    }

    #[Route(
        path: '/api/workspaces/{id}',
        name: 'api_workspace_delete',
        methods: Request::METHOD_DELETE,
    )]
    #[IsGranted(WorkspaceVoter::DELETE, subject: 'workspace')]
    public function delete(
        Workspace $workspace,
        WorkspaceService $service,
    ): Response {
        $service->delete($workspace);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
