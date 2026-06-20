<?php

declare(strict_types=1);

namespace App\Controller\Api\Workspace;

use App\DTO\CreateWorkspaceDTO;
use App\DTO\Response\WorkspaceListItemResponse;
use App\Entity\User;
use App\Enum\WorkspaceRole;
use App\Service\WorkspaceService;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class CreateWorkspaceController extends AbstractController
{
    public function __construct(
        private readonly WorkspaceService $workspaceService,
    ) {}

    #[OA\Post(
        path: '/api/workspaces',
        summary: 'Create a new workspace',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: new Model(type: CreateWorkspaceDTO::class))
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Workspace created',
                content: new OA\JsonContent(ref: new Model(type: WorkspaceListItemResponse::class))
            ),
            new OA\Response(response: 401, description: 'Not authenticated'),
            new OA\Response(response: 422, description: 'Validation error — name missing or exceeds 64 characters'),
        ]
    )]
    #[Route(
        path: '/api/workspaces',
        name: 'api_workspaces_create',
        methods: Request::METHOD_POST,
    )]
    public function __invoke(
        #[MapRequestPayload] CreateWorkspaceDTO $dto,
        #[CurrentUser] User $user,
    ): JsonResponse {
        $workspace = $this->workspaceService->create($dto->name, $user);

        return $this->json(
            WorkspaceListItemResponse::fromWorkspaceAndRole($workspace, WorkspaceRole::Owner),
            Response::HTTP_CREATED,
        );
    }
}
