<?php

declare(strict_types=1);

namespace App\Controller\Api\Workspace\Invitation;

use App\DTO\Response\InvitationOwnerViewResponse;
use App\Entity\Workspace;
use App\Repository\WorkspaceInvitationRepository;
use App\Security\Voter\WorkspaceVoter;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class ListInvitationsController extends AbstractController
{
    #[Route(
        path: '/api/workspaces/{workspaceId}/invitations',
        name: 'api_workspace_invitations_list',
        methods: Request::METHOD_GET,
    )]
    #[IsGranted(WorkspaceVoter::VIEW_INVITATIONS, subject: 'workspace')]
    public function __invoke(
        #[MapEntity(mapping: ['workspaceId' => 'id'])] Workspace $workspace,
        WorkspaceInvitationRepository $invitationRepository,
        ClockInterface $clock,
    ): JsonResponse {
        $now = $clock->now();
        $invitations = $invitationRepository->findAllByWorkspace($workspace);

        return $this->json(
            array_map(
                static fn ($inv) => InvitationOwnerViewResponse::fromInvitation($inv, $now),
                $invitations,
            ),
        );
    }
}
