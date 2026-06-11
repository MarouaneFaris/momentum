<?php

declare(strict_types=1);

namespace App\Controller\Api\Task;

use App\DTO\Response\TaskListItemResponse;
use App\Entity\Task;
use App\Entity\User;
use App\Entity\Workspace;
use App\Repository\TaskRepository;
use App\Security\Voter\WorkspaceVoter;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class ListMyTasksController extends AbstractController
{
    #[OA\Get(
        path: '/api/workspaces/{workspaceId}/tasks',
        summary: 'List tasks assigned to the authenticated user in a workspace',
        parameters: [
            new OA\Parameter(name: 'workspaceId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'limit', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
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
            new OA\Response(response: 404, description: 'Workspace not found'),
        ]
    )]
    #[Route(
        path: '/api/workspaces/{workspaceId}/tasks',
        name: 'api_my_tasks_list',
        methods: Request::METHOD_GET,
    )]
    #[IsGranted(WorkspaceVoter::VIEW, subject: 'workspace')]
    public function __invoke(
        #[CurrentUser] User $user,
        #[MapEntity(mapping: ['workspaceId' => 'id'])] Workspace $workspace,
        Request $request,
        TaskRepository $taskRepository,
    ): JsonResponse {
        $limitParam = $request->query->get('limit');
        $limit = $limitParam !== null ? (int) $limitParam : null;

        $tasks = $taskRepository->findByWorkspaceAndUser($workspace, $user, $limit);

        return $this->json(
            array_map(
                static fn (Task $t) => TaskListItemResponse::fromTask($t),
                $tasks,
            ),
        );
    }
}
