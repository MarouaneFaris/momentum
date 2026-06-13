<?php

declare(strict_types=1);

namespace App\Controller\Api\Task;

use App\DTO\Response\TaskListItemResponse;
use App\Entity\Project;
use App\Entity\Task;
use App\Entity\Workspace;
use App\Repository\TaskRepository;
use App\Security\Voter\TaskVoter;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class ListTasksController extends AbstractTaskController
{
    #[OA\Get(
        path: '/api/workspaces/{workspaceId}/projects/{projectId}/tasks',
        summary: 'List tasks for a project',
        parameters: [
            new OA\Parameter(name: 'workspaceId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'projectId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Task list',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: new Model(type: TaskListItemResponse::class))
                )
            ),
            new OA\Response(response: 401, description: 'Not authenticated'),
            new OA\Response(response: 403, description: 'Access denied'),
            new OA\Response(response: 404, description: 'Workspace or project not found'),
        ]
    )]
    #[Route(
        path: '/api/workspaces/{workspaceId}/projects/{projectId}/tasks',
        name: 'api_tasks_list',
        methods: Request::METHOD_GET,
    )]
    #[IsGranted(TaskVoter::VIEW, subject: 'project')]
    public function __invoke(
        #[MapEntity(mapping: ['workspaceId' => 'id'])] Workspace $workspace,
        #[MapEntity(mapping: ['projectId' => 'id'])] Project $project,
        TaskRepository $taskRepository,
    ): JsonResponse {
        $this->assertProjectBelongsToWorkspace($project, $workspace);

        $tasks = $taskRepository->findByProject($project);

        return $this->json(
            array_map(
                static fn (Task $t) => TaskListItemResponse::fromTask($t),
                $tasks,
            ),
        );
    }
}
