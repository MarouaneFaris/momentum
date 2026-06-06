<?php

declare(strict_types=1);

namespace App\Controller\Api\Task;

use App\DTO\CreateTaskDTO;
use App\DTO\Response\TaskListItemResponse;
use App\Entity\Project;
use App\Entity\User;
use App\Entity\Workspace;
use App\Repository\UserRepository;
use App\Security\Voter\TaskVoter;
use App\Service\TaskService;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class CreateTaskController extends AbstractController
{
    #[OA\Post(
        path: '/api/workspaces/{workspaceId}/projects/{projectId}/tasks',
        summary: 'Create a task in a project',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: new Model(type: CreateTaskDTO::class))
        ),
        parameters: [
            new OA\Parameter(name: 'workspaceId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'projectId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Task created',
                content: new OA\JsonContent(ref: new Model(type: TaskListItemResponse::class))
            ),
            new OA\Response(response: 400, description: 'Validation error'),
            new OA\Response(response: 401, description: 'Not authenticated'),
            new OA\Response(response: 403, description: 'Access denied'),
            new OA\Response(response: 404, description: 'Workspace or project not found'),
            new OA\Response(response: 422, description: 'Assignee not found or not eligible'),
        ]
    )]
    #[Route(
        path: '/api/workspaces/{workspaceId}/projects/{projectId}/tasks',
        name: 'api_tasks_create',
        methods: Request::METHOD_POST,
    )]
    #[IsGranted(TaskVoter::CREATE, subject: 'project')]
    public function __invoke(
        #[MapEntity(mapping: ['workspaceId' => 'id'])] Workspace $workspace,
        #[MapEntity(mapping: ['projectId' => 'id'])] Project $project,
        #[MapRequestPayload] CreateTaskDTO $dto,
        #[CurrentUser] User $user,
        TaskService $taskService,
        UserRepository $userRepository,
    ): JsonResponse {
        $projectWorkspaceId = $project->getWorkspace()->getId();
        $workspaceId = $workspace->getId();
        if ($projectWorkspaceId === null || $workspaceId === null || !$projectWorkspaceId->equals($workspaceId)) {
            throw new NotFoundHttpException();
        }

        $assignee = null;
        if ($dto->assigneeId !== null) {
            $assignee = $userRepository->find($dto->assigneeId);
            if ($assignee === null) {
                throw new UnprocessableEntityHttpException('Assignee not found');
            }
        }

        $task = $taskService->create(
            $project,
            $user,
            $dto->title,
            $dto->description,
            $assignee,
        );

        return $this->json(TaskListItemResponse::fromTask($task), Response::HTTP_CREATED);
    }
}
