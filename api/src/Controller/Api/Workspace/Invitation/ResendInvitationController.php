<?php

declare(strict_types=1);

namespace App\Controller\Api\Workspace\Invitation;

use App\Entity\Workspace;
use App\Entity\WorkspaceInvitation;
use App\Security\Voter\WorkspaceVoter;
use App\Service\MembershipService;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class ResendInvitationController extends AbstractController
{
    #[Route(
        path: '/api/workspaces/{workspaceId}/invitations/{invitationId}/resend',
        name: 'api_workspace_invitation_resend',
        methods: Request::METHOD_POST,
    )]
    #[IsGranted(WorkspaceVoter::INVITE, subject: 'workspace')]
    public function __invoke(
        #[MapEntity(mapping: ['workspaceId' => 'id'])] Workspace $workspace,
        #[MapEntity(mapping: ['invitationId' => 'id'])] WorkspaceInvitation $invitation,
        MembershipService $membershipService,
    ): JsonResponse {
        $membershipService->resend($invitation);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
