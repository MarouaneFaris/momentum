<?php

declare(strict_types=1);

namespace App\Controller\Api\Project\Member;

use App\DTO\AssignProjectMemberDTO;
use App\DTO\Response\ProjectMemberResponse;
use App\Entity\Project;
use App\Entity\Workspace;
use App\Enum\WorkspaceRole;
use App\Repository\UserProjectRepository;
use App\Repository\UserWorkspaceRepository;
use App\Security\Voter\ProjectVoter;
use App\Service\ProjectService;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class AssignProjectMemberController extends AbstractController
{
    #[Route(
        path: '/api/workspaces/{workspaceId}/projects/{projectId}/members',
        name: 'api_project_members_assign',
        methods: Request::METHOD_POST,
    )]
    #[IsGranted(ProjectVoter::MANAGE_MEMBERS, subject: 'project')]
    public function __invoke(
        #[MapEntity(mapping: ['workspaceId' => 'id'])] Workspace $workspace,
        #[MapEntity(mapping: ['projectId' => 'id'])] Project $project,
        #[MapRequestPayload] AssignProjectMemberDTO $dto,
        UserWorkspaceRepository $userWorkspaceRepository,
        UserProjectRepository $userProjectRepository,
        ProjectService $projectService,
    ): JsonResponse {
        $targetMembership = $userWorkspaceRepository->findOneByWorkspaceAndUserId($workspace, $dto->userId);

        if ($targetMembership === null) {
            throw new NotFoundHttpException('User is not a member of this workspace');
        }

        if ($targetMembership->getRole() !== WorkspaceRole::Guest) {
            throw new UnprocessableEntityHttpException('Only workspace Guests can be assigned to a project');
        }

        $existing = $userProjectRepository->findOneByProjectAndUser($project, $targetMembership->getUser());
        if ($existing !== null) {
            throw new ConflictHttpException('User is already assigned to this project');
        }

        $assignment = $projectService->assignGuest($project, $targetMembership->getUser());

        return $this->json(ProjectMemberResponse::fromUserProject($assignment), Response::HTTP_CREATED);
    }
}
