<?php

declare(strict_types=1);

namespace App\Controller\Api\Workspace\Member;

use App\Entity\Workspace;
use App\Repository\UserWorkspaceRepository;
use App\Security\Voter\WorkspaceVoter;
use App\Service\MembershipService;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class RemoveMemberController extends AbstractController
{
    public function __construct(
        private readonly UserWorkspaceRepository $userWorkspaceRepository,
        private readonly MembershipService $membershipService,
    ) {}

    #[Route(
        path: '/api/workspaces/{workspaceId}/members/{userId}',
        name: 'api_workspace_member_remove',
        methods: Request::METHOD_DELETE,
    )]
    #[IsGranted(WorkspaceVoter::REMOVE_MEMBER, subject: 'workspace')]
    public function __invoke(
        #[MapEntity(mapping: ['workspaceId' => 'id'])] Workspace $workspace,
        string $userId,
    ): Response {
        $membership = $this->userWorkspaceRepository->findOneByWorkspaceAndUserId($workspace, $userId);
        if (!$membership) {
            throw new NotFoundHttpException('Member not found in this workspace');
        }

        $this->membershipService->removeMember($membership);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
