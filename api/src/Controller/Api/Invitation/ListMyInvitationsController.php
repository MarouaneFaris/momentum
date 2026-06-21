<?php

declare(strict_types=1);

namespace App\Controller\Api\Invitation;

use App\DTO\Response\InvitationInviteeViewResponse;
use App\Entity\User;
use App\Repository\WorkspaceInvitationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class ListMyInvitationsController extends AbstractController
{
    public function __construct(
        private readonly WorkspaceInvitationRepository $invitationRepository,
        private readonly ClockInterface $clock,
    ) {}

    #[Route(
        path: '/api/invitations',
        name: 'api_invitations_list',
        methods: Request::METHOD_GET,
    )]
    public function __invoke(
        #[CurrentUser] User $currentUser,
    ): JsonResponse {
        $invitations = $this->invitationRepository->findPendingByInvitee($currentUser, $this->clock->now());

        return $this->json(
            array_map(
                static fn ($inv) => InvitationInviteeViewResponse::fromInvitation($inv),
                $invitations,
            ),
        );
    }
}
