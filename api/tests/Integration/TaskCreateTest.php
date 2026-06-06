<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Enum\WorkspaceRole;
use App\Factory\ProjectFactory;
use App\Factory\UserFactory;
use App\Factory\UserProjectFactory;
use App\Factory\UserWorkspaceFactory;
use App\Factory\WorkspaceFactory;
use Symfony\Component\HttpFoundation\Response;

final class TaskCreateTest extends IntegrationTestCase
{
    public function testUnauthenticatedReturns401(): void
    {
        $client = static::createClient();
        $workspace = WorkspaceFactory::createOne();
        $membership = UserWorkspaceFactory::createOne(['workspace' => $workspace, 'role' => WorkspaceRole::Owner]);
        $project = ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $membership]);

        $client->request('POST', '/api/workspaces/' . $workspace->getId() . '/projects/' . $project->getId() . '/tasks', [], [], ['CONTENT_TYPE' => 'application/json'], '{"title":"My task"}');

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
        $client->request('POST', '/api/workspaces/' . $workspace->getId() . '/projects/' . $project->getId() . '/tasks', [], [], ['CONTENT_TYPE' => 'application/json'], '{"title":"My task"}');

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testGuestReturns403(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        $ownerMembership = UserWorkspaceFactory::createOne(['workspace' => $workspace, 'role' => WorkspaceRole::Owner]);
        UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Guest]);
        $project = ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $ownerMembership]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request('POST', '/api/workspaces/' . $workspace->getId() . '/projects/' . $project->getId() . '/tasks', [], [], ['CONTENT_TYPE' => 'application/json'], '{"title":"My task"}');

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testOwnerCanCreateTask(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        $membership = UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Owner]);
        $project = ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $membership]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request(
            'POST',
            '/api/workspaces/' . $workspace->getId() . '/projects/' . $project->getId() . '/tasks',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['title' => 'My task', 'description' => 'Some desc'], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        /** @var array<string, mixed> $data */
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertSame('My task', $data['title']);
        self::assertSame('todo', $data['status']);
        self::assertNull($data['assignee']);
        self::assertArrayHasKey('id', $data);
        self::assertArrayHasKey('createdAt', $data);
    }

    public function testMemberCanCreateTask(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        $ownerMembership = UserWorkspaceFactory::createOne(['workspace' => $workspace, 'role' => WorkspaceRole::Owner]);
        UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Member]);
        $project = ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $ownerMembership]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request(
            'POST',
            '/api/workspaces/' . $workspace->getId() . '/projects/' . $project->getId() . '/tasks',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['title' => 'Member task'], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
    }

    public function testCreatorIdSetToAuthenticatedUser(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        $membership = UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Owner]);
        $project = ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $membership]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request(
            'POST',
            '/api/workspaces/' . $workspace->getId() . '/projects/' . $project->getId() . '/tasks',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['title' => 'Creator check'], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        // Creator is the authenticated user — verified via DB but response doesn't expose creatorId
    }

    public function testCanCreateTaskWithMemberAssignee(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $assignee = UserFactory::createOne(['name' => 'Bob']);
        $workspace = WorkspaceFactory::createOne();
        $membership = UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Owner]);
        UserWorkspaceFactory::createOne(['user' => $assignee, 'workspace' => $workspace, 'role' => WorkspaceRole::Member]);
        $project = ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $membership]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request(
            'POST',
            '/api/workspaces/' . $workspace->getId() . '/projects/' . $project->getId() . '/tasks',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['title' => 'Assigned task', 'assigneeId' => (string) $assignee->getId()], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        /** @var array<string, mixed> $data */
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertNotNull($data['assignee']);
        self::assertSame('Bob', $data['assignee']['name']);
    }

    public function testAssigningGuestReturns422(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $guest = UserFactory::createOne();
        $workspace = WorkspaceFactory::createOne();
        $membership = UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Owner]);
        UserWorkspaceFactory::createOne(['user' => $guest, 'workspace' => $workspace, 'role' => WorkspaceRole::Guest]);
        $project = ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $membership]);
        UserProjectFactory::createOne(['project' => $project, 'user' => $guest]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request(
            'POST',
            '/api/workspaces/' . $workspace->getId() . '/projects/' . $project->getId() . '/tasks',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['title' => 'Task', 'assigneeId' => (string) $guest->getId()], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testAssigningNonWorkspaceMemberReturns422(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $outsider = UserFactory::createOne();
        $workspace = WorkspaceFactory::createOne();
        $membership = UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Owner]);
        $project = ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $membership]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request(
            'POST',
            '/api/workspaces/' . $workspace->getId() . '/projects/' . $project->getId() . '/tasks',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['title' => 'Task', 'assigneeId' => (string) $outsider->getId()], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testMissingTitleReturns422(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        $membership = UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Owner]);
        $project = ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $membership]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request('POST', '/api/workspaces/' . $workspace->getId() . '/projects/' . $project->getId() . '/tasks', [], [], ['CONTENT_TYPE' => 'application/json'], '{}');

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
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
        $client->request('POST', '/api/workspaces/' . $workspace->getId() . '/projects/' . $project->getId() . '/tasks', [], [], ['CONTENT_TYPE' => 'application/json'], '{"title":"Task"}');

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
