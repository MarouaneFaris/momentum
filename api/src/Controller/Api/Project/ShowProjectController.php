<?php

declare(strict_types=1);

namespace App\Controller\Api\Project;

use App\DTO\Response\ProjectDetailResponse;
use App\Entity\Project;
use App\Entity\Workspace;
use App\Repository\TaskRepository;
use App\Repository\UserProjectRepository;
use App\Security\Voter\TaskVoter;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class ShowProjectController extends AbstractController
{
    public function __construct(
        private readonly TaskRepository $taskRepository,
        private readonly UserProjectRepository $userProjectRepository,
    ) {}

    #[OA\Get(
        path: '/api/workspaces/{workspaceId}/projects/{projectId}',
        summary: 'Get project detail with task stats and member count',
        parameters: [
            new OA\Parameter(name: 'workspaceId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'projectId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Project detail',
                content: new OA\JsonContent(ref: new Model(type: ProjectDetailResponse::class))
            ),
            new OA\Response(response: 401, description: 'Not authenticated'),
            new OA\Response(response: 403, description: 'Access denied'),
            new OA\Response(response: 404, description: 'Workspace or project not found'),
        ]
    )]
    #[Route(
        path: '/api/workspaces/{workspaceId}/projects/{projectId}',
        name: 'api_project_show',
        methods: Request::METHOD_GET,
    )]
    #[IsGranted(TaskVoter::VIEW, subject: 'project')]
    public function __invoke(
        #[MapEntity(mapping: ['workspaceId' => 'id'])] Workspace $workspace,
        #[MapEntity(mapping: ['projectId' => 'id'])] Project $project,
    ): JsonResponse {
        $projectWorkspaceId = $project->getWorkspace()->getId();
        $workspaceId = $workspace->getId();
        if ($projectWorkspaceId === null || $workspaceId === null || !$projectWorkspaceId->equals($workspaceId)) {
            throw new NotFoundHttpException();
        }

        $taskStats = $this->taskRepository->getStatsForProject($project);
        $memberCount = \count($this->userProjectRepository->findByProject($project));

        return $this->json(
            ProjectDetailResponse::fromProject($project, $taskStats, $memberCount),
        );
    }
}
