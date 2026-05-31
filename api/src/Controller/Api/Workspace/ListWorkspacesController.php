<?php

declare(strict_types=1);

namespace App\Controller\Api\Workspace;

use App\DTO\Response\WorkspaceListItemResponse;
use App\Entity\User;
use App\Entity\UserWorkspace;
use App\Repository\UserWorkspaceRepository;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class ListWorkspacesController extends AbstractController
{
    #[OA\Get(
        path: '/api/workspaces',
        summary: 'List all workspaces the authenticated user belongs to',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Workspace list',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: new Model(type: WorkspaceListItemResponse::class))
                )
            ),
            new OA\Response(response: 401, description: 'Not authenticated'),
        ]
    )]
    #[Route(
        path: '/api/workspaces',
        name: 'api_workspaces_list',
        methods: Request::METHOD_GET,
    )]
    public function __invoke(
        #[CurrentUser] User $user,
        UserWorkspaceRepository $userWorkspaceRepository,
    ): JsonResponse {
        $userWorkspaces = $userWorkspaceRepository->findByUser($user);

        return $this->json(
            array_map(
                static fn (UserWorkspace $uw) => WorkspaceListItemResponse::fromWorkspaceAndRole($uw->getWorkspace(), $uw->getRole()),
                $userWorkspaces,
            ),
        );
    }
}
