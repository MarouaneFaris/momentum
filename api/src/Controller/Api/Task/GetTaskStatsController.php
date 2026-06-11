<?php

declare(strict_types=1);

namespace App\Controller\Api\Task;

use App\Entity\User;
use App\Entity\Workspace;
use App\Repository\TaskRepository;
use App\Security\Voter\WorkspaceVoter;
use OpenApi\Attributes as OA;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class GetTaskStatsController extends AbstractController
{
    #[OA\Get(
        path: '/api/workspaces/{workspaceId}/tasks/stats',
        summary: 'Get task stats for the authenticated user in a workspace',
        parameters: [
            new OA\Parameter(name: 'workspaceId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Task stats',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'open', type: 'integer'),
                        new OA\Property(property: 'in_progress', type: 'integer'),
                        new OA\Property(property: 'done_this_week', type: 'integer'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 401, description: 'Not authenticated'),
            new OA\Response(response: 403, description: 'Access denied'),
            new OA\Response(response: 404, description: 'Workspace not found'),
        ]
    )]
    #[Route(
        path: '/api/workspaces/{workspaceId}/tasks/stats',
        name: 'api_tasks_stats',
        methods: Request::METHOD_GET,
    )]
    #[IsGranted(WorkspaceVoter::VIEW, subject: 'workspace')]
    public function __invoke(
        #[MapEntity(mapping: ['workspaceId' => 'id'])] Workspace $workspace,
        TaskRepository $taskRepository,
    ): JsonResponse {
        $user = $this->getUser();
        assert($user instanceof User);

        return $this->json($taskRepository->getStatsByWorkspaceAndUser($workspace, $user));
    }
}
