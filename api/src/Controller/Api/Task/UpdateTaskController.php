<?php

declare(strict_types=1);

namespace App\Controller\Api\Task;

use App\DTO\Response\TaskDetailResponse;
use App\DTO\UpdateTaskDTO;
use App\Entity\Project;
use App\Entity\Task;
use App\Entity\User;
use App\Entity\Workspace;
use App\Repository\UserRepository;
use App\Security\Voter\TaskVoter;
use App\Service\TaskService;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class UpdateTaskController extends AbstractTaskController
{
    #[OA\Patch(
        path: '/api/workspaces/{workspaceId}/projects/{projectId}/tasks/{taskId}',
        summary: 'Update a task',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: new Model(type: UpdateTaskDTO::class))
        ),
        parameters: [
            new OA\Parameter(name: 'workspaceId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'projectId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'taskId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Task updated',
                content: new OA\JsonContent(ref: new Model(type: TaskDetailResponse::class))
            ),
            new OA\Response(response: 400, description: 'Validation error'),
            new OA\Response(response: 401, description: 'Not authenticated'),
            new OA\Response(response: 403, description: 'Access denied'),
            new OA\Response(response: 404, description: 'Workspace, project, or task not found'),
            new OA\Response(response: 422, description: 'Invalid assignee'),
        ]
    )]
    #[Route(
        path: '/api/workspaces/{workspaceId}/projects/{projectId}/tasks/{taskId}',
        name: 'api_tasks_update',
        methods: Request::METHOD_PATCH,
    )]
    #[IsGranted(TaskVoter::EDIT, subject: 'task')]
    public function __invoke(
        #[MapEntity(mapping: ['workspaceId' => 'id'])] Workspace $workspace,
        #[MapEntity(mapping: ['projectId' => 'id'])] Project $project,
        #[MapEntity(mapping: ['taskId' => 'id'])] Task $task,
        #[MapRequestPayload] UpdateTaskDTO $dto,
        #[CurrentUser] User $user,
        TaskService $taskService,
        UserRepository $userRepository,
    ): JsonResponse {
        $this->assertProjectBelongsToWorkspace($project, $workspace);
        $this->assertTaskBelongsToProject($task, $project);

        $newAssignee = null;
        if ($dto->assigneeId !== null) {
            $newAssignee = $userRepository->find($dto->assigneeId);
            if ($newAssignee === null) {
                throw new UnprocessableEntityHttpException('Assignee not found');
            }
        }

        $task = $taskService->update($task, $user, $workspace, $dto, $newAssignee);

        return $this->json(TaskDetailResponse::fromTask($task), Response::HTTP_OK);
    }
}
