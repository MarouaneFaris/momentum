<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Enum\WorkspaceRole;
use App\Factory\ProjectFactory;
use App\Factory\TaskFactory;
use App\Factory\UserFactory;
use App\Factory\UserWorkspaceFactory;
use App\Factory\WorkspaceFactory;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Response;

final class MyTasksListTest extends IntegrationTestCase
{
    public function testUnauthenticatedReturns401(): void
    {
        $client = static::createClient();
        $workspace = WorkspaceFactory::createOne();

        $client->request('GET', '/api/workspaces/' . $workspace->getId() . '/tasks');

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testNonMemberReturns403(): void
    {
        $client = static::createClient();
        UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request('GET', '/api/workspaces/' . $workspace->getId() . '/tasks');

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testReturnsOnlyTasksAssignedToCurrentUser(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $otherUser = UserFactory::createOne();
        $workspace = WorkspaceFactory::createOne();
        $membership = UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Member]);
        UserWorkspaceFactory::createOne(['user' => $otherUser, 'workspace' => $workspace, 'role' => WorkspaceRole::Member]);
        $project = ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $membership]);

        TaskFactory::createMany(2, ['project' => $project, 'creator' => $user, 'assignee' => $user]);
        TaskFactory::createMany(3, ['project' => $project, 'creator' => $otherUser, 'assignee' => $otherUser]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request('GET', '/api/workspaces/' . $workspace->getId() . '/tasks');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        /** @var list<mixed> $data */
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertCount(2, $data);
    }

    public function testExcludesTasksFromOtherWorkspaces(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        $otherWorkspace = WorkspaceFactory::createOne();
        $membership = UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Member]);
        $otherMembership = UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $otherWorkspace, 'role' => WorkspaceRole::Member]);
        $project = ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $membership]);
        $otherProject = ProjectFactory::createOne(['workspace' => $otherWorkspace, 'owner' => $otherMembership]);

        TaskFactory::createOne(['project' => $project, 'creator' => $user, 'assignee' => $user]);
        TaskFactory::createMany(5, ['project' => $otherProject, 'creator' => $user, 'assignee' => $user]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request('GET', '/api/workspaces/' . $workspace->getId() . '/tasks');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        /** @var list<mixed> $data */
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertCount(1, $data);
    }

    public function testOrderedByUpdatedAtDesc(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        $membership = UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Member]);
        $project = ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $membership]);

        $taskA = TaskFactory::createOne(['project' => $project, 'creator' => $user, 'assignee' => $user, 'title' => 'Task A']);
        $taskB = TaskFactory::createOne(['project' => $project, 'creator' => $user, 'assignee' => $user, 'title' => 'Task B']);
        $taskC = TaskFactory::createOne(['project' => $project, 'creator' => $user, 'assignee' => $user, 'title' => 'Task C']);

        $conn = static::getContainer()->get(Connection::class);
        assert($conn instanceof Connection);

        $idA = $taskA->getId();
        $idB = $taskB->getId();
        $idC = $taskC->getId();
        assert($idA !== null && $idB !== null && $idC !== null);

        $conn->executeStatement('UPDATE task SET updated_at = :t WHERE id = :id', ['t' => '2025-01-01 00:00:01', 'id' => $idA->toBinary()]);
        $conn->executeStatement('UPDATE task SET updated_at = :t WHERE id = :id', ['t' => '2025-01-01 00:00:03', 'id' => $idB->toBinary()]);
        $conn->executeStatement('UPDATE task SET updated_at = :t WHERE id = :id', ['t' => '2025-01-01 00:00:02', 'id' => $idC->toBinary()]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request('GET', '/api/workspaces/' . $workspace->getId() . '/tasks');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        /** @var list<array<string, mixed>> $data */
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertCount(3, $data);
        self::assertSame('Task B', $data[0]['title']);
        self::assertSame('Task C', $data[1]['title']);
        self::assertSame('Task A', $data[2]['title']);
    }

    public function testLimitParamCapsResults(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        $membership = UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Member]);
        $project = ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $membership]);

        TaskFactory::createMany(15, ['project' => $project, 'creator' => $user, 'assignee' => $user]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request('GET', '/api/workspaces/' . $workspace->getId() . '/tasks?limit=10');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        /** @var list<mixed> $data */
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertCount(10, $data);
    }

    public function testNoLimitReturnsAll(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        $membership = UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Member]);
        $project = ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $membership]);

        TaskFactory::createMany(15, ['project' => $project, 'creator' => $user, 'assignee' => $user]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request('GET', '/api/workspaces/' . $workspace->getId() . '/tasks');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        /** @var list<mixed> $data */
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertCount(15, $data);
    }

    public function testResponseShape(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        $membership = UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Member]);
        $project = ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $membership]);

        TaskFactory::createOne(['project' => $project, 'creator' => $user, 'assignee' => $user, 'title' => 'My Task']);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request('GET', '/api/workspaces/' . $workspace->getId() . '/tasks');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        /** @var list<array<string, mixed>> $data */
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertCount(1, $data);
        self::assertArrayHasKey('id', $data[0]);
        self::assertArrayHasKey('title', $data[0]);
        self::assertArrayHasKey('status', $data[0]);
        self::assertArrayHasKey('assignee', $data[0]);
        self::assertArrayHasKey('createdAt', $data[0]);
        self::assertArrayHasKey('creatorId', $data[0]);
    }
}
