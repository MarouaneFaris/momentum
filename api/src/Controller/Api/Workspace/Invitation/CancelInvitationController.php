<?php

declare(strict_types=1);

namespace App\Controller\Api\Workspace\Invitation;

use App\Entity\User;
use App\Entity\Workspace;
use App\Entity\WorkspaceInvitation;
use App\Security\Voter\WorkspaceVoter;
use App\Service\MembershipService;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class CancelInvitationController extends AbstractController
{
    public function __construct(
        private readonly MembershipService $membershipService,
    ) {}

    #[Route(
        path: '/api/workspaces/{workspaceId}/invitations/{invitationId}',
        name: 'api_workspace_invitation_cancel',
        methods: Request::METHOD_DELETE,
    )]
    #[IsGranted(WorkspaceVoter::CANCEL_INVITATION, subject: 'workspace')]
    public function __invoke(
        #[CurrentUser] User $currentUser,
        #[MapEntity(mapping: ['workspaceId' => 'id'])] Workspace $workspace,
        #[MapEntity(mapping: ['invitationId' => 'id'])] WorkspaceInvitation $invitation,
    ): Response {
        $this->membershipService->cancel($workspace, $invitation, $currentUser);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
