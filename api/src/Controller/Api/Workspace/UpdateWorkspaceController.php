<?php

declare(strict_types=1);

namespace App\Controller\Api\Workspace;

use App\DTO\CreateWorkspaceDTO;
use App\DTO\Response\WorkspaceListItemResponse;
use App\Entity\Workspace;
use App\Enum\WorkspaceRole;
use App\Security\Voter\WorkspaceVoter;
use App\Service\WorkspaceService;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class UpdateWorkspaceController extends AbstractController
{
    #[OA\Patch(
        path: '/api/workspaces/{id}',
        summary: 'Rename a workspace (owner only)',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: new Model(type: CreateWorkspaceDTO::class))
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Workspace renamed',
                content: new OA\JsonContent(ref: new Model(type: WorkspaceListItemResponse::class))
            ),
            new OA\Response(response: 401, description: 'Not authenticated'),
            new OA\Response(response: 403, description: 'Not workspace owner'),
            new OA\Response(response: 404, description: 'Workspace not found'),
            new OA\Response(response: 422, description: 'Validation error — name missing or exceeds 64 characters'),
        ]
    )]
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
