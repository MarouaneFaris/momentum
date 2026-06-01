<?php

declare(strict_types=1);

namespace App\Controller\Api\Workspace\Member;

use App\DTO\ChangeRoleDTO;
use App\DTO\Response\MemberResponse;
use App\Entity\User;
use App\Entity\Workspace;
use App\Enum\WorkspaceRole;
use App\Repository\UserWorkspaceRepository;
use App\Security\Voter\WorkspaceVoter;
use App\Service\MembershipService;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class ChangeRoleController extends AbstractController
{
    #[Route(
        path: '/api/workspaces/{workspaceId}/members/{userId}',
        name: 'api_workspace_member_change_role',
        methods: Request::METHOD_PATCH,
    )]
    #[IsGranted(WorkspaceVoter::CHANGE_ROLE, subject: 'workspace')]
    public function __invoke(
        #[MapEntity(mapping: ['workspaceId' => 'id'])] Workspace $workspace,
        string $userId,
        #[MapRequestPayload] ChangeRoleDTO $dto,
        #[CurrentUser] User $currentUser,
        UserWorkspaceRepository $userWorkspaceRepository,
        MembershipService $membershipService,
    ): JsonResponse {
        $membership = $userWorkspaceRepository->findOneByWorkspaceAndUserId($workspace, $userId);
        if (!$membership) {
            throw new NotFoundHttpException('Member not found in this workspace');
        }

        $membershipService->changeRole($membership, WorkspaceRole::from($dto->role), $currentUser);

        return $this->json(MemberResponse::fromUserWorkspace($membership));
    }
}
