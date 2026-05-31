<?php

declare(strict_types=1);

namespace App\Controller\Api\Workspace\Member;

use App\Entity\User;
use App\Entity\Workspace;
use App\Service\MembershipService;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class LeaveWorkspaceController extends AbstractController
{
    #[Route(
        path: '/api/workspaces/{workspaceId}/members/me',
        name: 'api_workspace_member_leave',
        methods: Request::METHOD_DELETE,
        priority: 1,
    )]
    public function __invoke(
        #[MapEntity(mapping: ['workspaceId' => 'id'])] Workspace $workspace,
        #[CurrentUser] User $currentUser,
        MembershipService $membershipService,
    ): Response {
        $membershipService->leave($workspace, $currentUser);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
