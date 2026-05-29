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
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
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
        UserWorkspaceRepository $userWorkspaceRepository,
    ): JsonResponse {
        $memberships = $userWorkspaceRepository->findByUser($user);

        return $this->json(
            array_map(
                static fn (array $m) => WorkspaceListItemResponse::fromWorkspaceAndRole($m['workspace'], $m['role']),
                $memberships,
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
        EntityManagerInterface $em,
    ): JsonResponse {
        $workspace = new Workspace();
        $workspace->setName($dto->name);
        $workspace->setCreator($user);
        $em->persist($workspace);

        $userWorkspace = new UserWorkspace();
        $userWorkspace->setUser($user);
        $userWorkspace->setWorkspace($workspace);
        $userWorkspace->setRole(WorkspaceRole::Owner);
        $em->persist($userWorkspace);

        $em->flush();

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
    public function show(
        Workspace $workspace,
        #[CurrentUser] User $user,
        UserWorkspaceRepository $userWorkspaceRepository,
    ): JsonResponse {
        $this->denyAccessUnlessGranted(WorkspaceVoter::VIEW, $workspace);

        $role = $userWorkspaceRepository->findRoleByUserAndWorkspace($user, $workspace);

        return $this->json(
            WorkspaceListItemResponse::fromWorkspaceAndRole($workspace, $role),
        );
    }

    #[Route(
        path: '/api/workspaces/{id}',
        name: 'api_workspace_update',
        methods: Request::METHOD_PATCH,
    )]
    public function update(
        Workspace $workspace,
        #[MapRequestPayload] CreateWorkspaceDTO $dto,
        EntityManagerInterface $em,
    ): JsonResponse {
        $this->denyAccessUnlessGranted(WorkspaceVoter::EDIT, $workspace);

        $workspace->setName($dto->name);
        $em->flush();

        return $this->json(
            WorkspaceListItemResponse::fromWorkspaceAndRole($workspace, WorkspaceRole::Owner),
        );
    }
}
