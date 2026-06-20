<?php

declare(strict_types=1);

namespace App\Controller\Api\Task;

use App\Entity\Project;
use App\Entity\Task;
use App\Entity\Workspace;
use App\Security\Voter\TaskVoter;
use App\Service\TaskService;
use OpenApi\Attributes as OA;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class DeleteTaskController extends AbstractTaskController
{
    public function __construct(private readonly TaskService $taskService) {}

    #[OA\Delete(
        path: '/api/workspaces/{workspaceId}/projects/{projectId}/tasks/{taskId}',
        summary: 'Delete a task',
        parameters: [
            new OA\Parameter(name: 'workspaceId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'projectId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'taskId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Task deleted'),
            new OA\Response(response: 401, description: 'Not authenticated'),
            new OA\Response(response: 403, description: 'Access denied'),
            new OA\Response(response: 404, description: 'Workspace, project, or task not found'),
        ]
    )]
    #[Route(
        path: '/api/workspaces/{workspaceId}/projects/{projectId}/tasks/{taskId}',
        name: 'api_tasks_delete',
        methods: Request::METHOD_DELETE,
    )]
    #[IsGranted(TaskVoter::DELETE, subject: 'task')]
    public function __invoke(
        #[MapEntity(mapping: ['workspaceId' => 'id'])] Workspace $workspace,
        #[MapEntity(mapping: ['projectId' => 'id'])] Project $project,
        #[MapEntity(mapping: ['taskId' => 'id'])] Task $task,
    ): Response {
        $this->assertProjectBelongsToWorkspace($project, $workspace);
        $this->assertTaskBelongsToProject($task, $project);

        $this->taskService->delete($task);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
