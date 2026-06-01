<?php

declare(strict_types=1);

namespace App\Controller\Api\Invitation;

use App\Entity\User;
use App\Entity\WorkspaceInvitation;
use App\Service\MembershipService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class AcceptInvitationController extends AbstractController
{
    #[Route(
        path: '/api/invitations/{id}/accept',
        name: 'api_invitation_accept',
        methods: Request::METHOD_PUT,
    )]
    public function __invoke(
        WorkspaceInvitation $invitation,
        #[CurrentUser] User $currentUser,
        MembershipService $membershipService,
    ): Response {
        $membershipService->accept($invitation, $currentUser);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
