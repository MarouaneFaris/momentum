<?php

declare(strict_types=1);

namespace App\Controller\Api\Project\Member;

use App\DTO\Response\ProjectMemberResponse;
use App\Entity\Project;
use App\Entity\Workspace;
use App\Repository\UserProjectRepository;
use App\Security\Voter\ProjectVoter;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class ListProjectMembersController extends AbstractController
{
    #[Route(
        path: '/api/workspaces/{workspaceId}/projects/{projectId}/members',
        name: 'api_project_members_list',
        methods: Request::METHOD_GET,
    )]
    #[IsGranted(ProjectVoter::MANAGE_MEMBERS, subject: 'project')]
    public function __invoke(
        #[MapEntity(mapping: ['workspaceId' => 'id'])] Workspace $workspace,
        #[MapEntity(mapping: ['projectId' => 'id'])] Project $project,
        UserProjectRepository $userProjectRepository,
    ): JsonResponse {
        $members = $userProjectRepository->findByProject($project);

        return $this->json(
            array_map(
                static fn ($up) => ProjectMemberResponse::fromUserProject($up),
                $members,
            ),
        );
    }
}
