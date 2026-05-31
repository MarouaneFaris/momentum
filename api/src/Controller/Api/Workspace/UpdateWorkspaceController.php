<?php

declare(strict_types=1);

namespace App\Controller\Api\Workspace;

use App\DTO\CreateWorkspaceDTO;
use App\DTO\Response\WorkspaceListItemResponse;
use App\Entity\Workspace;
use App\Enum\WorkspaceRole;
use App\Security\Voter\WorkspaceVoter;
use App\Service\WorkspaceService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class UpdateWorkspaceController extends AbstractController
{
    #[Route(
        path: '/api/workspaces/{id}',
        name: 'api_workspace_update',
        methods: Request::METHOD_PATCH,
    )]
    #[IsGranted(WorkspaceVoter::EDIT, subject: 'workspace')]
    public function __invoke(
        Workspace $workspace,
        #[MapRequestPayload] CreateWorkspaceDTO $dto,
        WorkspaceService $service,
    ): JsonResponse {
        $service->rename($workspace, $dto->name);

        return $this->json(
            WorkspaceListItemResponse::fromWorkspaceAndRole($workspace, WorkspaceRole::Owner),
        );
    }
}
