<?php

declare(strict_types=1);

namespace App\Controller\Api\Workspace;

use App\DTO\CreateWorkspaceDTO;
use App\DTO\Response\WorkspaceListItemResponse;
use App\Entity\User;
use App\Enum\WorkspaceRole;
use App\Service\WorkspaceService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class CreateWorkspaceController extends AbstractController
{
    #[Route(
        path: '/api/workspaces',
        name: 'api_workspaces_create',
        methods: Request::METHOD_POST,
    )]
    public function __invoke(
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
}
