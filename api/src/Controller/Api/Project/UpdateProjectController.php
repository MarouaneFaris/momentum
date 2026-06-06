<?php

declare(strict_types=1);

namespace App\Controller\Api\Project;

use App\DTO\Response\ProjectListItemResponse;
use App\DTO\UpdateProjectDTO;
use App\Entity\Project;
use App\Entity\Workspace;
use App\Enum\ProjectStatus;
use App\Security\Voter\ProjectVoter;
use App\Service\ProjectService;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class UpdateProjectController extends AbstractController
{
    #[OA\Patch(
        path: '/api/workspaces/{workspaceId}/projects/{projectId}',
        summary: 'Update a project',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: new Model(type: UpdateProjectDTO::class))
        ),
        parameters: [
            new OA\Parameter(name: 'workspaceId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'projectId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Project updated',
                content: new OA\JsonContent(ref: new Model(type: ProjectListItemResponse::class))
            ),
            new OA\Response(response: 401, description: 'Not authenticated'),
            new OA\Response(response: 403, description: 'Not workspace Owner or project owner'),
            new OA\Response(response: 404, description: 'Workspace or project not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    #[Route(
        path: '/api/workspaces/{workspaceId}/projects/{projectId}',
        name: 'api_projects_update',
        methods: Request::METHOD_PATCH,
    )]
    #[IsGranted(ProjectVoter::EDIT, subject: 'project')]
    public function __invoke(
        #[MapEntity(mapping: ['workspaceId' => 'id'])] Workspace $workspace,
        #[MapEntity(mapping: ['projectId' => 'id'])] Project $project,
        #[MapRequestPayload] UpdateProjectDTO $dto,
        ProjectService $projectService,
    ): JsonResponse {
        if ((string) $project->getWorkspace()->getId() !== (string) $workspace->getId()) {
            throw $this->createNotFoundException('Project not found in this workspace');
        }

        try {
            $projectService->update(
                $project,
                $dto->name,
                $dto->description,
                $dto->status !== null ? ProjectStatus::from($dto->status) : null,
            );
        } catch (\LogicException $e) {
            return $this->json(['message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->json(ProjectListItemResponse::fromProject($project));
    }
}
