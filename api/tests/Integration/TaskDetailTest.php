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

final class TaskDetailTest extends IntegrationTestCase
{
    public function testUnauthenticatedReturns401(): void
    {
        $client = static::createClient();
        $workspace = WorkspaceFactory::createOne();
        $membership = UserWorkspaceFactory::createOne(['workspace' => $workspace, 'role' => WorkspaceRole::Owner]);
        $project = ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $membership]);
        $task = TaskFactory::createOne(['project' => $project, 'creator' => $membership->getUser()]);

        $client->request('GET', '/api/workspaces/' . $workspace->getId() . '/projects/' . $project->getId() . '/tasks/' . $task->getId());

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testNonMemberReturns403(): void
    {
        $client = static::createClient();
        UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        $membership = UserWorkspaceFactory::createOne(['workspace' => $workspace, 'role' => WorkspaceRole::Owner]);
        $project = ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $membership]);
        $task = TaskFactory::createOne(['project' => $project, 'creator' => $membership->getUser()]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request('GET', '/api/workspaces/' . $workspace->getId() . '/projects/' . $project->getId() . '/tasks/' . $task->getId());

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testOwnerCanViewTaskDetail(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD, 'name' => 'Owner User']);
        $workspace = WorkspaceFactory::createOne();
        $membership = UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Owner]);
        $project = ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $membership, 'status' => ProjectStatus::Active]);
        $assignee = UserFactory::createOne(['name' => 'Alice']);
        $task = TaskFactory::createOne([
            'project' => $project,
            'creator' => $user,
            'assignee' => $assignee,
            'title' => 'My task',
            'description' => 'Some details',
            'status' => TaskStatus::InProgress,
        ]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request('GET', '/api/workspaces/' . $workspace->getId() . '/projects/' . $project->getId() . '/tasks/' . $task->getId());

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        /** @var array<string, mixed> $data */
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertSame((string) $task->getId(), $data['id']);
        self::assertSame('My task', $data['title']);
        self::assertSame('Some details', $data['description']);
        self::assertSame('in-progress', $data['status']);
        self::assertArrayHasKey('creator', $data);
        self::assertSame('Owner User', $data['creator']['name']);
        self::assertArrayHasKey('assignee', $data);
        self::assertSame('Alice', $data['assignee']['name']);
        self::assertArrayHasKey('createdAt', $data);
        self::assertArrayHasKey('updatedAt', $data);
    }

    public function testMemberCanViewTaskDetail(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        $ownerMembership = UserWorkspaceFactory::createOne(['workspace' => $workspace, 'role' => WorkspaceRole::Owner]);
        UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Member]);
        $project = ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $ownerMembership, 'status' => ProjectStatus::Active]);
        $task = TaskFactory::createOne(['project' => $project, 'creator' => $ownerMembership->getUser()]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request('GET', '/api/workspaces/' . $workspace->getId() . '/projects/' . $project->getId() . '/tasks/' . $task->getId());

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testGuestWithAssignmentCanViewTaskDetail(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        $ownerMembership = UserWorkspaceFactory::createOne(['workspace' => $workspace, 'role' => WorkspaceRole::Owner]);
        UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Guest]);
        $project = ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $ownerMembership, 'status' => ProjectStatus::Active]);
        UserProjectFactory::createOne(['project' => $project, 'user' => $user]);
        $task = TaskFactory::createOne(['project' => $project, 'creator' => $ownerMembership->getUser()]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request('GET', '/api/workspaces/' . $workspace->getId() . '/projects/' . $project->getId() . '/tasks/' . $task->getId());

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
        $task = TaskFactory::createOne(['project' => $project, 'creator' => $ownerMembership->getUser()]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request('GET', '/api/workspaces/' . $workspace->getId() . '/projects/' . $project->getId() . '/tasks/' . $task->getId());

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testUnknownTaskReturns404(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        $membership = UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Owner]);
        $project = ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $membership]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request('GET', '/api/workspaces/' . $workspace->getId() . '/projects/' . $project->getId() . '/tasks/00000000-0000-0000-0000-000000000000');

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testTaskFromDifferentProjectReturns404(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        $membership = UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Owner]);
        $project = ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $membership]);
        $otherProject = ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $membership]);
        $task = TaskFactory::createOne(['project' => $otherProject, 'creator' => $user]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request('GET', '/api/workspaces/' . $workspace->getId() . '/projects/' . $project->getId() . '/tasks/' . $task->getId());

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testTaskWithNullDescriptionReturnsNull(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        $membership = UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Owner]);
        $project = ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $membership]);
        $task = TaskFactory::createOne(['project' => $project, 'creator' => $user, 'description' => null]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request('GET', '/api/workspaces/' . $workspace->getId() . '/projects/' . $project->getId() . '/tasks/' . $task->getId());

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        /** @var array<string, mixed> $data */
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertNull($data['description']);
    }
}
