<?php

declare(strict_types=1);

namespace App\Controller\Api\Workspace;

use App\Entity\Workspace;
use App\Security\Voter\WorkspaceVoter;
use App\Service\WorkspaceService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class DeleteWorkspaceController extends AbstractController
{
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
