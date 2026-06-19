<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Enum\ProjectStatus;
use App\Enum\TaskStatus;
use App\Enum\WorkspaceRole;
use App\Factory\ProjectFactory;
use App\Factory\TaskFactory;
use App\Factory\UserFactory;
use App\Factory\UserWorkspaceFactory;
use App\Factory\WorkspaceFactory;
use App\Repository\UserWorkspaceRepository;
use App\Service\MembershipService;
use Symfony\Component\HttpFoundation\Response;

/**
 * Reproduces the scenario where a user who WAS a member is removed by the owner
 * and then hits workspace-scoped pages. Every endpoint must return 403, never 500.
 */
final class RemovedMemberAccessTest extends IntegrationTestCase
{
    public function testRemovedMemberGetsForbiddenNotServerError(): void
    {
        $client = static::createClient();

        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $owner = UserFactory::createOne();
        $workspace = WorkspaceFactory::createOne();
        $ownerMembership = UserWorkspaceFactory::createOne(['user' => $owner, 'workspace' => $workspace, 'role' => WorkspaceRole::Owner]);
        $membership = UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Member]);

        $project = ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $ownerMembership, 'status' => ProjectStatus::Active]);
        TaskFactory::createOne(['project' => $project, 'creator' => $user, 'assignee' => $user, 'status' => TaskStatus::Todo]);

        // Remove the member through the real removal flow.
        $repo = static::getContainer()->get(UserWorkspaceRepository::class);
        $service = static::getContainer()->get(MembershipService::class);
        \assert($repo instanceof UserWorkspaceRepository);
        \assert($service instanceof MembershipService);
        $service->removeMember($membership);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);

        $id = $workspace->getId();

        foreach ([
            "/api/workspaces/$id",
            "/api/workspaces/$id/projects",
            "/api/workspaces/$id/tasks/stats",
            "/api/workspaces/$id/projects/{$project->getId()}/tasks",
        ] as $path) {
            $client->request('GET', $path);
            self::assertSame(
                Response::HTTP_FORBIDDEN,
                $client->getResponse()->getStatusCode(),
                "Expected 403 for $path, got {$client->getResponse()->getStatusCode()}: {$client->getResponse()->getContent()}",
            );
        }
    }
}
