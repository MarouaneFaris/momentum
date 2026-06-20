<?php

declare(strict_types=1);

namespace App\Controller\Api\Project;

use App\Entity\Project;
use App\Entity\Workspace;
use App\Security\Voter\ProjectVoter;
use App\Service\ProjectService;
use OpenApi\Attributes as OA;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class DeleteProjectController extends AbstractController
{
    public function __construct(private readonly ProjectService $projectService) {}

    #[OA\Delete(
        path: '/api/workspaces/{workspaceId}/projects/{projectId}',
        summary: 'Delete a project',
        parameters: [
            new OA\Parameter(name: 'workspaceId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'projectId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Project deleted'),
            new OA\Response(response: 401, description: 'Not authenticated'),
            new OA\Response(response: 403, description: 'Not workspace Owner or project owner'),
            new OA\Response(response: 404, description: 'Workspace or project not found'),
        ]
    )]
    #[Route(
        path: '/api/workspaces/{workspaceId}/projects/{projectId}',
        name: 'api_projects_delete',
        methods: Request::METHOD_DELETE,
    )]
    #[IsGranted(ProjectVoter::DELETE, subject: 'project')]
    public function __invoke(
        #[MapEntity(mapping: ['workspaceId' => 'id'])] Workspace $workspace,
        #[MapEntity(mapping: ['projectId' => 'id'])] Project $project,
    ): Response {
        if ((string) $project->getWorkspace()->getId() !== (string) $workspace->getId()) {
            throw $this->createNotFoundException('Project not found in this workspace');
        }

        $this->projectService->delete($project);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
