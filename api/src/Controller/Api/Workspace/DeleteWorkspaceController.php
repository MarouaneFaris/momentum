<?php

declare(strict_types=1);

namespace App\Controller\Api\Workspace;

use App\Entity\Workspace;
use App\Security\Voter\WorkspaceVoter;
use App\Service\WorkspaceService;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class DeleteWorkspaceController extends AbstractController
{
    #[OA\Delete(
        path: '/api/workspaces/{id}',
        summary: 'Delete a workspace and all its data (owner only)',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Workspace deleted'),
            new OA\Response(response: 401, description: 'Not authenticated'),
            new OA\Response(response: 403, description: 'Not workspace owner'),
            new OA\Response(response: 404, description: 'Workspace not found'),
        ]
    )]
    #[Route(
        path: '/api/workspaces/{id}',
        name: 'api_workspace_delete',
        methods: Request::METHOD_DELETE,
    )]
    #[IsGranted(WorkspaceVoter::DELETE, subject: 'workspace')]
    public function __invoke(
        Workspace $workspace,
        WorkspaceService $service,
    ): Response {
        $service->delete($workspace);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
