<?php

declare(strict_types=1);

namespace App\Controller\Api\Project;

use App\DTO\CreateProjectDTO;
use App\DTO\Response\ProjectListItemResponse;
use App\Entity\User;
use App\Entity\Workspace;
use App\Enum\ProjectStatus;
use App\Repository\UserWorkspaceRepository;
use App\Security\Voter\ProjectVoter;
use App\Service\ProjectService;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class CreateProjectController extends AbstractController
{
    #[OA\Post(
        path: '/api/workspaces/{workspaceId}/projects',
        summary: 'Create a new project in a workspace',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: new Model(type: CreateProjectDTO::class))
        ),
        parameters: [
            new OA\Parameter(name: 'workspaceId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Project created',
                content: new OA\JsonContent(ref: new Model(type: ProjectListItemResponse::class))
            ),
            new OA\Response(response: 401, description: 'Not authenticated'),
            new OA\Response(response: 403, description: 'Not a workspace Owner or Member'),
            new OA\Response(response: 404, description: 'Workspace not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    #[Route(
        path: '/api/workspaces/{workspaceId}/projects',
        name: 'api_projects_create',
        methods: Request::METHOD_POST,
    )]
    #[IsGranted(ProjectVoter::CREATE, subject: 'workspace')]
    public function __invoke(
        #[MapEntity(mapping: ['workspaceId' => 'id'])] Workspace $workspace,
        #[MapRequestPayload] CreateProjectDTO $dto,
        #[CurrentUser] User $user,
        UserWorkspaceRepository $userWorkspaceRepository,
        ProjectService $projectService,
    ): JsonResponse {
        $membership = $userWorkspaceRepository->findOneBy(['user' => $user, 'workspace' => $workspace]);
        assert($membership !== null);

        $project = $projectService->create(
            $workspace,
            $membership,
            $dto->name,
            $dto->description,
            $dto->status !== null ? ProjectStatus::from($dto->status) : ProjectStatus::Active,
        );

        return $this->json(
            ProjectListItemResponse::fromProject($project),
            Response::HTTP_CREATED,
        );
    }
}
