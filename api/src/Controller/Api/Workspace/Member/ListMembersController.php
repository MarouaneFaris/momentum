<?php

declare(strict_types=1);

namespace App\Controller\Api\Workspace\Member;

use App\DTO\Response\MemberResponse;
use App\Entity\Workspace;
use App\Repository\UserWorkspaceRepository;
use App\Security\Voter\WorkspaceVoter;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class ListMembersController extends AbstractController
{
    #[Route(
        path: '/api/workspaces/{workspaceId}/members',
        name: 'api_workspace_members_list',
        methods: Request::METHOD_GET,
    )]
    #[IsGranted(WorkspaceVoter::VIEW_MEMBERS, subject: 'workspace')]
    public function __invoke(
        #[MapEntity(mapping: ['workspaceId' => 'id'])] Workspace $workspace,
        UserWorkspaceRepository $userWorkspaceRepository,
    ): JsonResponse {
        $members = $userWorkspaceRepository->findMembersByWorkspace($workspace);

        return $this->json(
            array_map(
                static fn ($uw) => MemberResponse::fromUserWorkspace($uw),
                $members,
            ),
        );
    }
}
