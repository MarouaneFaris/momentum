<?php

declare(strict_types=1);

namespace App\Controller\Api\Task;

use App\DTO\Response\TaskDetailResponse;
use App\Entity\Project;
use App\Entity\Task;
use App\Entity\Workspace;
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

final class GetTaskController extends AbstractController
{
    #[OA\Get(
        path: '/api/workspaces/{workspaceId}/projects/{projectId}/tasks/{taskId}',
        summary: 'Get task detail',
        parameters: [
            new OA\Parameter(name: 'workspaceId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'projectId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'taskId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Task detail',
                content: new OA\JsonContent(ref: new Model(type: TaskDetailResponse::class))
            ),
            new OA\Response(response: 401, description: 'Not authenticated'),
            new OA\Response(response: 403, description: 'Access denied'),
            new OA\Response(response: 404, description: 'Workspace, project, or task not found'),
        ]
    )]
    #[Route(
        path: '/api/workspaces/{workspaceId}/projects/{projectId}/tasks/{taskId}',
        name: 'api_tasks_get',
        methods: Request::METHOD_GET,
    )]
    #[IsGranted(TaskVoter::VIEW, subject: 'task')]
    public function __invoke(
        #[MapEntity(mapping: ['workspaceId' => 'id'])] Workspace $workspace,
        #[MapEntity(mapping: ['projectId' => 'id'])] Project $project,
        #[MapEntity(mapping: ['taskId' => 'id'])] Task $task,
    ): JsonResponse {
        $projectWorkspaceId = $project->getWorkspace()->getId();
        $workspaceId = $workspace->getId();
        if ($projectWorkspaceId === null || $workspaceId === null || !$projectWorkspaceId->equals($workspaceId)) {
            throw new NotFoundHttpException();
        }

        $taskProjectId = $task->getProject()->getId();
        $projectId = $project->getId();
        if ($taskProjectId === null || $projectId === null || !$taskProjectId->equals($projectId)) {
            throw new NotFoundHttpException();
        }

        return $this->json(TaskDetailResponse::fromTask($task));
    }
}
