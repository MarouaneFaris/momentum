<?php

declare(strict_types=1);

namespace App\Controller\Api\Workspace\Invitation;

use App\DTO\InviteDTO;
use App\DTO\Response\InvitationOwnerViewResponse;
use App\Entity\User;
use App\Entity\Workspace;
use App\Enum\WorkspaceRole;
use App\Security\Voter\WorkspaceVoter;
use App\Service\MembershipService;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class CreateInvitationController extends AbstractController
{
    #[Route(
        path: '/api/workspaces/{workspaceId}/invitations',
        name: 'api_workspace_invitation_create',
        methods: Request::METHOD_POST,
    )]
    #[IsGranted(WorkspaceVoter::INVITE, subject: 'workspace')]
    public function __invoke(
        #[MapEntity(mapping: ['workspaceId' => 'id'])] Workspace $workspace,
        #[MapRequestPayload] InviteDTO $dto,
        #[CurrentUser] User $currentUser,
        MembershipService $membershipService,
        ClockInterface $clock,
    ): JsonResponse {
        $invitation = $membershipService->invite(
            $workspace,
            $currentUser,
            $dto->email,
            WorkspaceRole::from($dto->role),
        );

        return $this->json(
            InvitationOwnerViewResponse::fromInvitation($invitation, $clock->now()),
            Response::HTTP_CREATED,
        );
    }
}
