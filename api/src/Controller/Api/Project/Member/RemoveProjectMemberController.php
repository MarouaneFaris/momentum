<?php

declare(strict_types=1);

namespace App\Controller\Api\Project\Member;

use App\Entity\Project;
use App\Entity\Workspace;
use App\Repository\UserProjectRepository;
use App\Security\Voter\ProjectVoter;
use App\Service\ProjectService;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class RemoveProjectMemberController extends AbstractController
{
    #[Route(
        path: '/api/workspaces/{workspaceId}/projects/{projectId}/members/{userId}',
        name: 'api_project_members_remove',
        methods: Request::METHOD_DELETE,
    )]
    #[IsGranted(ProjectVoter::MANAGE_MEMBERS, subject: 'project')]
    public function __invoke(
        #[MapEntity(mapping: ['workspaceId' => 'id'])] Workspace $workspace,
        #[MapEntity(mapping: ['projectId' => 'id'])] Project $project,
        string $userId,
        UserProjectRepository $userProjectRepository,
        ProjectService $projectService,
    ): Response {
        $assignment = $userProjectRepository->findOneByProjectAndUserId($project, $userId);

        if ($assignment === null) {
            throw new NotFoundHttpException('User is not assigned to this project');
        }

        $projectService->removeGuest($assignment);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
