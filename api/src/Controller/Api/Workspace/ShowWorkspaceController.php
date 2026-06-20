<?php

declare(strict_types=1);

namespace App\Controller\Api\Workspace;

use App\DTO\Response\WorkspaceListItemResponse;
use App\Entity\User;
use App\Entity\Workspace;
use App\Repository\UserWorkspaceRepository;
use App\Security\Voter\WorkspaceVoter;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class ShowWorkspaceController extends AbstractController
{
    public function __construct(
        private readonly UserWorkspaceRepository $userWorkspaceRepository,
    ) {}

    #[OA\Get(
        path: '/api/workspaces/{id}',
        summary: 'Get a single workspace by ID',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Workspace details',
                content: new OA\JsonContent(ref: new Model(type: WorkspaceListItemResponse::class))
            ),
            new OA\Response(response: 401, description: 'Not authenticated'),
            new OA\Response(response: 403, description: 'Not a workspace member'),
            new OA\Response(response: 404, description: 'Workspace not found'),
        ]
    )]
    #[Route(
        path: '/api/workspaces/{id}',
        name: 'api_workspace_show',
        methods: Request::METHOD_GET,
    )]
    #[IsGranted(WorkspaceVoter::VIEW, subject: 'workspace')]
    public function __invoke(
        Workspace $workspace,
        #[CurrentUser] User $user,
    ): JsonResponse {
        $role = $this->userWorkspaceRepository->findRoleByUserAndWorkspace($user, $workspace);
        assert($role !== null);

        return $this->json(
            WorkspaceListItemResponse::fromWorkspaceAndRole($workspace, $role),
        );
    }
}
