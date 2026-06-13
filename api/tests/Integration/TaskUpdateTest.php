<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Enum\WorkspaceRole;
use App\Factory\ProjectFactory;
use App\Factory\TaskFactory;
use App\Factory\UserFactory;
use App\Factory\UserWorkspaceFactory;
use App\Factory\WorkspaceFactory;
use Symfony\Component\HttpFoundation\Response;

final class TaskUpdateTest extends IntegrationTestCase
{
    public function testUnauthenticatedReturns401(): void
    {
        $client = static::createClient();
        $workspace = WorkspaceFactory::createOne();
        $membership = UserWorkspaceFactory::createOne(['workspace' => $workspace, 'role' => WorkspaceRole::Owner]);
        $project = ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $membership]);
        $task = TaskFactory::createOne(['project' => $project, 'creator' => $membership->getUser()]);

        $client->request('PATCH', '/api/workspaces/' . $workspace->getId() . '/projects/' . $project->getId() . '/tasks/' . $task->getId(), [], [], ['CONTENT_TYPE' => 'application/json'], '{"status":"done"}');

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testGuestReturns403(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        $ownerMembership = UserWorkspaceFactory::createOne(['workspace' => $workspace, 'role' => WorkspaceRole::Owner]);
        UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Guest]);
        $project = ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $ownerMembership]);
        $task = TaskFactory::createOne(['project' => $project, 'creator' => $ownerMembership->getUser()]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request('PATCH', '/api/workspaces/' . $workspace->getId() . '/projects/' . $project->getId() . '/tasks/' . $task->getId(), [], [], ['CONTENT_TYPE' => 'application/json'], '{"status":"done"}');

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testOwnerCanUpdateAllFields(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        $membership = UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Owner]);
        $project = ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $membership]);
        $task = TaskFactory::createOne(['project' => $project, 'creator' => $membership->getUser(), 'title' => 'Original']);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request(
            'PATCH',
            '/api/workspaces/' . $workspace->getId() . '/projects/' . $project->getId() . '/tasks/' . $task->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['title' => 'Updated', 'status' => 'done', 'description' => 'New desc'], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        /** @var array<string, mixed> $data */
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertSame('Updated', $data['title']);
        self::assertSame('done', $data['status']);
        self::assertSame('New desc', $data['description']);
    }

    public function testCreatorCanUpdateAllFields(): void
    {
        $client = static::createClient();
        $creator = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        $ownerMembership = UserWorkspaceFactory::createOne(['workspace' => $workspace, 'role' => WorkspaceRole::Owner]);
        UserWorkspaceFactory::createOne(['user' => $creator, 'workspace' => $workspace, 'role' => WorkspaceRole::Member]);
        $project = ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $ownerMembership]);
        $task = TaskFactory::createOne(['project' => $project, 'creator' => $creator]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request(
            'PATCH',
            '/api/workspaces/' . $workspace->getId() . '/projects/' . $project->getId() . '/tasks/' . $task->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['title' => 'Creator updated', 'status' => 'in-progress'], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        /** @var array<string, mixed> $data */
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertSame('Creator updated', $data['title']);
        self::assertSame('in-progress', $data['status']);
    }

    public function testMemberNonCreatorCanUpdateStatusOnly(): void
    {
        $client = static::createClient();
        $member = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        $ownerMembership = UserWorkspaceFactory::createOne(['workspace' => $workspace, 'role' => WorkspaceRole::Owner]);
        UserWorkspaceFactory::createOne(['user' => $member, 'workspace' => $workspace, 'role' => WorkspaceRole::Member]);
        $project = ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $ownerMembership]);
        $task = TaskFactory::createOne(['project' => $project, 'creator' => $ownerMembership->getUser(), 'title' => 'Original']);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request(
            'PATCH',
            '/api/workspaces/' . $workspace->getId() . '/projects/' . $project->getId() . '/tasks/' . $task->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['status' => 'in-progress'], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        /** @var array<string, mixed> $data */
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertSame('in-progress', $data['status']);
        self::assertSame('Original', $data['title']);
    }

    public function testMemberNonCreatorCannotUpdateTitle(): void
    {
        $client = static::createClient();
        $member = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        $ownerMembership = UserWorkspaceFactory::createOne(['workspace' => $workspace, 'role' => WorkspaceRole::Owner]);
        UserWorkspaceFactory::createOne(['user' => $member, 'workspace' => $workspace, 'role' => WorkspaceRole::Member]);
        $project = ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $ownerMembership]);
        $task = TaskFactory::createOne(['project' => $project, 'creator' => $ownerMembership->getUser()]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request(
            'PATCH',
            '/api/workspaces/' . $workspace->getId() . '/projects/' . $project->getId() . '/tasks/' . $task->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['title' => 'Forbidden update'], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        /** @var array<string, mixed> $body */
        $body = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertSame('WORKSPACE_FORBIDDEN', $body['code']);
    }

    public function testTaskFromDifferentProjectReturns404(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        $membership = UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Owner]);
        $project = ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $membership]);
        $otherProject = ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $membership]);
        $task = TaskFactory::createOne(['project' => $otherProject, 'creator' => $membership->getUser()]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request(
            'PATCH',
            '/api/workspaces/' . $workspace->getId() . '/projects/' . $project->getId() . '/tasks/' . $task->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            '{"status":"done"}',
        );

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testUpdatedResponseContainsExpectedFields(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        $membership = UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Owner]);
        $project = ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $membership]);
        $task = TaskFactory::createOne(['project' => $project, 'creator' => $membership->getUser()]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request(
            'PATCH',
            '/api/workspaces/' . $workspace->getId() . '/projects/' . $project->getId() . '/tasks/' . $task->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            '{"status":"in-progress"}',
        );

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        /** @var array<string, mixed> $data */
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertArrayHasKey('id', $data);
        self::assertArrayHasKey('title', $data);
        self::assertArrayHasKey('status', $data);
        self::assertArrayHasKey('description', $data);
        self::assertArrayHasKey('creator', $data);
        self::assertArrayHasKey('updatedAt', $data);
    }
}
