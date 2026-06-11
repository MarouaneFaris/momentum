<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Enum\TaskStatus;
use App\Enum\WorkspaceRole;
use App\Factory\ProjectFactory;
use App\Factory\TaskFactory;
use App\Factory\UserFactory;
use App\Factory\UserWorkspaceFactory;
use App\Factory\WorkspaceFactory;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Response;

final class TaskStatsTest extends IntegrationTestCase
{
    public function testUnauthenticatedReturns401(): void
    {
        $client = static::createClient();
        $workspace = WorkspaceFactory::createOne();

        $client->request('GET', '/api/workspaces/' . $workspace->getId() . '/tasks/stats');

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testNonMemberReturns403(): void
    {
        $client = static::createClient();
        UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request('GET', '/api/workspaces/' . $workspace->getId() . '/tasks/stats');

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testReturnsCorrectCountsPerStatus(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        $membership = UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Member]);
        $project = ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $membership]);

        TaskFactory::createMany(2, ['project' => $project, 'creator' => $user, 'assignee' => $user, 'status' => TaskStatus::Todo]);
        TaskFactory::createMany(3, ['project' => $project, 'creator' => $user, 'assignee' => $user, 'status' => TaskStatus::InProgress]);
        TaskFactory::createMany(4, ['project' => $project, 'creator' => $user, 'assignee' => $user, 'status' => TaskStatus::Done]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request('GET', '/api/workspaces/' . $workspace->getId() . '/tasks/stats');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        /** @var array<string, int> $data */
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertSame(2, $data['open']);
        self::assertSame(3, $data['in_progress']);
        self::assertSame(4, $data['done_this_week']);
    }

    public function testDoneThisWeekExcludesOlderTasks(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        $membership = UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Member]);
        $project = ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $membership]);

        TaskFactory::createOne(['project' => $project, 'creator' => $user, 'assignee' => $user, 'status' => TaskStatus::Done]);
        $oldTask = TaskFactory::createOne(['project' => $project, 'creator' => $user, 'assignee' => $user, 'status' => TaskStatus::Done]);

        $conn = static::getContainer()->get(Connection::class);
        assert($conn instanceof Connection);
        $oldTaskId = $oldTask->getId();
        assert($oldTaskId !== null);
        $lastWeek = (new \DateTimeImmutable())->modify('-8 days')->format('Y-m-d H:i:s');
        $conn->executeStatement(
            'UPDATE task SET updated_at = :updatedAt WHERE id = :id',
            ['updatedAt' => $lastWeek, 'id' => $oldTaskId->toBinary()],
        );

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request('GET', '/api/workspaces/' . $workspace->getId() . '/tasks/stats');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        /** @var array<string, int> $data */
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertSame(1, $data['done_this_week']);
    }

    public function testScopedToCurrentUserOnly(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $otherUser = UserFactory::createOne();
        $workspace = WorkspaceFactory::createOne();
        $membership = UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Member]);
        UserWorkspaceFactory::createOne(['user' => $otherUser, 'workspace' => $workspace, 'role' => WorkspaceRole::Member]);
        $project = ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $membership]);

        TaskFactory::createOne(['project' => $project, 'creator' => $user, 'assignee' => $user, 'status' => TaskStatus::Todo]);
        TaskFactory::createMany(3, ['project' => $project, 'creator' => $otherUser, 'assignee' => $otherUser, 'status' => TaskStatus::Todo]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request('GET', '/api/workspaces/' . $workspace->getId() . '/tasks/stats');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        /** @var array<string, int> $data */
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertSame(1, $data['open']);
    }

    public function testScopedToCurrentWorkspaceOnly(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        $otherWorkspace = WorkspaceFactory::createOne();
        $membership = UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Member]);
        $otherMembership = UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $otherWorkspace, 'role' => WorkspaceRole::Member]);
        $project = ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $membership]);
        $otherProject = ProjectFactory::createOne(['workspace' => $otherWorkspace, 'owner' => $otherMembership]);

        TaskFactory::createOne(['project' => $project, 'creator' => $user, 'assignee' => $user, 'status' => TaskStatus::Todo]);
        TaskFactory::createMany(5, ['project' => $otherProject, 'creator' => $user, 'assignee' => $user, 'status' => TaskStatus::Todo]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request('GET', '/api/workspaces/' . $workspace->getId() . '/tasks/stats');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        /** @var array<string, int> $data */
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertSame(1, $data['open']);
    }
}
