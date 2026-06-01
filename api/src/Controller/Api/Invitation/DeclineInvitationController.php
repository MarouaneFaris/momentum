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

final class DeclineInvitationController extends AbstractController
{
    #[Route(
        path: '/api/invitations/{id}/decline',
        name: 'api_invitation_decline',
        methods: Request::METHOD_DELETE,
    )]
    public function __invoke(
        WorkspaceInvitation $invitation,
        #[CurrentUser] User $currentUser,
        MembershipService $membershipService,
    ): Response {
        $membershipService->decline($invitation, $currentUser);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
