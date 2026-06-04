<?php

declare(strict_types=1);

namespace App\Controller\Api\Project;

use App\DTO\Response\ProjectListItemResponse;
use App\Entity\Project;
use App\Entity\User;
use App\Entity\Workspace;
use App\Repository\ProjectRepository;
use App\Repository\UserWorkspaceRepository;
use App\Security\Voter\ProjectVoter;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class ListProjectsController extends AbstractController
{
    #[OA\Get(
        path: '/api/workspaces/{workspaceId}/projects',
        summary: 'List projects in a workspace (filtered by caller role)',
        parameters: [
            new OA\Parameter(name: 'workspaceId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Project list',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: new Model(type: ProjectListItemResponse::class))
                )
            ),
            new OA\Response(response: 401, description: 'Not authenticated'),
            new OA\Response(response: 403, description: 'Not a workspace member'),
            new OA\Response(response: 404, description: 'Workspace not found'),
        ]
    )]
    #[Route(
        path: '/api/workspaces/{workspaceId}/projects',
        name: 'api_projects_list',
        methods: Request::METHOD_GET,
    )]
    #[IsGranted(ProjectVoter::VIEW, subject: 'workspace')]
    public function __invoke(
        #[MapEntity(mapping: ['workspaceId' => 'id'])] Workspace $workspace,
        #[CurrentUser] User $user,
        UserWorkspaceRepository $userWorkspaceRepository,
        ProjectRepository $projectRepository,
    ): JsonResponse {
        $membership = $userWorkspaceRepository->findOneBy(['user' => $user, 'workspace' => $workspace]);
        assert($membership !== null);

        $projects = $projectRepository->findVisibleForMember($workspace, $membership);

        return $this->json(
            array_map(
                static fn (Project $p) => ProjectListItemResponse::fromProject($p),
                $projects,
            ),
        );
    }
}
