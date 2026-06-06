<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Enum\ProjectStatus;
use App\Enum\TaskStatus;
use App\Enum\WorkspaceRole;
use App\Factory\ProjectFactory;
use App\Factory\TaskFactory;
use App\Factory\UserFactory;
use App\Factory\UserProjectFactory;
use App\Factory\UserWorkspaceFactory;
use App\Factory\WorkspaceFactory;
use Symfony\Component\HttpFoundation\Response;

final class TaskListTest extends IntegrationTestCase
{
    public function testUnauthenticatedReturns401(): void
    {
        $client = static::createClient();
        $workspace = WorkspaceFactory::createOne();
        $membership = UserWorkspaceFactory::createOne(['workspace' => $workspace, 'role' => WorkspaceRole::Owner]);
        $project = ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $membership]);

        $client->request('GET', '/api/workspaces/' . $workspace->getId() . '/projects/' . $project->getId() . '/tasks');

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testNonMemberReturns403(): void
    {
        $client = static::createClient();
        UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        $membership = UserWorkspaceFactory::createOne(['workspace' => $workspace, 'role' => WorkspaceRole::Owner]);
        $project = ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $membership]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request('GET', '/api/workspaces/' . $workspace->getId() . '/projects/' . $project->getId() . '/tasks');

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testOwnerSeesAllTasks(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        $membership = UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Owner]);
        $project = ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $membership, 'status' => ProjectStatus::Active]);

        TaskFactory::createOne(['project' => $project, 'creator' => $user, 'title' => 'Task A', 'status' => TaskStatus::Todo]);
        TaskFactory::createOne(['project' => $project, 'creator' => $user, 'title' => 'Task B', 'status' => TaskStatus::InProgress]);
        TaskFactory::createOne(['project' => $project, 'creator' => $user, 'title' => 'Task C', 'status' => TaskStatus::Done]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request('GET', '/api/workspaces/' . $workspace->getId() . '/projects/' . $project->getId() . '/tasks');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        /** @var list<array<string, mixed>> $data */
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertCount(3, $data);
        self::assertArrayHasKey('id', $data[0]);
        self::assertArrayHasKey('title', $data[0]);
        self::assertArrayHasKey('status', $data[0]);
        self::assertArrayHasKey('assignee', $data[0]);
        self::assertArrayHasKey('createdAt', $data[0]);
    }

    public function testMemberSeesAllTasks(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        $ownerMembership = UserWorkspaceFactory::createOne(['workspace' => $workspace, 'role' => WorkspaceRole::Owner]);
        UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Member]);
        $project = ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $ownerMembership, 'status' => ProjectStatus::Active]);

        TaskFactory::createMany(2, ['project' => $project, 'creator' => $user]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request('GET', '/api/workspaces/' . $workspace->getId() . '/projects/' . $project->getId() . '/tasks');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        /** @var list<array<string, mixed>> $data */
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertCount(2, $data);
    }

    public function testGuestWithAssignmentSeesTaskList(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        $ownerMembership = UserWorkspaceFactory::createOne(['workspace' => $workspace, 'role' => WorkspaceRole::Owner]);
        UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Guest]);
        $project = ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $ownerMembership, 'status' => ProjectStatus::Active]);

        UserProjectFactory::createOne(['project' => $project, 'user' => $user]);
        TaskFactory::createOne(['project' => $project, 'creator' => $ownerMembership->getUser()]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request('GET', '/api/workspaces/' . $workspace->getId() . '/projects/' . $project->getId() . '/tasks');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testGuestWithoutAssignmentReturns403(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        $ownerMembership = UserWorkspaceFactory::createOne(['workspace' => $workspace, 'role' => WorkspaceRole::Owner]);
        UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Guest]);
        $project = ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $ownerMembership, 'status' => ProjectStatus::Active]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request('GET', '/api/workspaces/' . $workspace->getId() . '/projects/' . $project->getId() . '/tasks');

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testEmptyProjectReturnsEmptyArray(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        $membership = UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Owner]);
        $project = ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $membership]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request('GET', '/api/workspaces/' . $workspace->getId() . '/projects/' . $project->getId() . '/tasks');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        /** @var list<mixed> $data */
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertSame([], $data);
    }

    public function testAssignedTaskReturnsAssigneeSummary(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $assignee = UserFactory::createOne(['name' => 'Alice']);
        $workspace = WorkspaceFactory::createOne();
        $membership = UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Owner]);
        $project = ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $membership]);

        TaskFactory::createOne(['project' => $project, 'creator' => $user, 'assignee' => $assignee]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request('GET', '/api/workspaces/' . $workspace->getId() . '/projects/' . $project->getId() . '/tasks');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        /** @var list<array<string, mixed>> $data */
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertCount(1, $data);
        self::assertNotNull($data[0]['assignee']);
        self::assertSame('Alice', $data[0]['assignee']['name']);
    }

    public function testProjectFromDifferentWorkspaceReturns404(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Owner]);

        $otherWorkspace = WorkspaceFactory::createOne();
        $otherMembership = UserWorkspaceFactory::createOne(['workspace' => $otherWorkspace, 'role' => WorkspaceRole::Owner]);
        $project = ProjectFactory::createOne(['workspace' => $otherWorkspace, 'owner' => $otherMembership]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request('GET', '/api/workspaces/' . $workspace->getId() . '/projects/' . $project->getId() . '/tasks');

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
