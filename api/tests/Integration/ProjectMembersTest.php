<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Enum\ProjectStatus;
use App\Enum\WorkspaceRole;
use App\Factory\ProjectFactory;
use App\Factory\UserFactory;
use App\Factory\UserProjectFactory;
use App\Factory\UserWorkspaceFactory;
use App\Factory\WorkspaceFactory;
use Symfony\Component\HttpFoundation\Response;

final class ProjectMembersTest extends IntegrationTestCase
{
    // -------------------------------------------------------------------------
    // LIST
    // -------------------------------------------------------------------------

    public function testListUnauthenticatedReturns401(): void
    {
        $client = static::createClient();
        $workspace = WorkspaceFactory::createOne();
        $membership = UserWorkspaceFactory::createOne(['workspace' => $workspace, 'role' => WorkspaceRole::Owner]);
        $project = ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $membership]);

        $client->request('GET', '/api/workspaces/' . $workspace->getId() . '/projects/' . $project->getId() . '/members');

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testListByGuestReturns403(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        $ownerMembership = UserWorkspaceFactory::createOne(['workspace' => $workspace, 'role' => WorkspaceRole::Owner]);
        UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Guest]);
        $project = ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $ownerMembership]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request('GET', '/api/workspaces/' . $workspace->getId() . '/projects/' . $project->getId() . '/members');

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testListByNonOwnerMemberOnOtherMembersProjectReturns403(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        $ownerMembership = UserWorkspaceFactory::createOne(['workspace' => $workspace, 'role' => WorkspaceRole::Owner]);
        UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Member]);
        $project = ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $ownerMembership]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request('GET', '/api/workspaces/' . $workspace->getId() . '/projects/' . $project->getId() . '/members');

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testListByOwnerReturnsAssignedGuests(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        $membership = UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Owner]);
        $project = ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $membership]);

        $guest = UserFactory::createOne();
        UserWorkspaceFactory::createOne(['user' => $guest, 'workspace' => $workspace, 'role' => WorkspaceRole::Guest]);
        UserProjectFactory::createOne(['project' => $project, 'user' => $guest]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request('GET', '/api/workspaces/' . $workspace->getId() . '/projects/' . $project->getId() . '/members');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        /** @var list<array<string, mixed>> $data */
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertCount(1, $data);
        self::assertArrayHasKey('id', $data[0]);
        self::assertArrayHasKey('name', $data[0]);
        self::assertArrayHasKey('email', $data[0]);
        self::assertArrayHasKey('assignedAt', $data[0]);
    }

    // -------------------------------------------------------------------------
    // ASSIGN
    // -------------------------------------------------------------------------

    public function testAssignUnauthenticatedReturns401(): void
    {
        $client = static::createClient();
        $workspace = WorkspaceFactory::createOne();
        $membership = UserWorkspaceFactory::createOne(['workspace' => $workspace, 'role' => WorkspaceRole::Owner]);
        $project = ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $membership]);

        $client->request('POST', '/api/workspaces/' . $workspace->getId() . '/projects/' . $project->getId() . '/members', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['userId' => '018f5c2e-1234-7abc-8def-abcdef012345']) ?: '');

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testAssignByGuestReturns403(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        $ownerMembership = UserWorkspaceFactory::createOne(['workspace' => $workspace, 'role' => WorkspaceRole::Owner]);
        UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Guest]);
        $project = ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $ownerMembership]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request('POST', '/api/workspaces/' . $workspace->getId() . '/projects/' . $project->getId() . '/members', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['userId' => '018f5c2e-1234-7abc-8def-abcdef012345']) ?: '');

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testAssignGuestReturns201(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        $membership = UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Owner]);
        $project = ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $membership, 'status' => ProjectStatus::Active]);

        $guest = UserFactory::createOne();
        UserWorkspaceFactory::createOne(['user' => $guest, 'workspace' => $workspace, 'role' => WorkspaceRole::Guest]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request(
            'POST',
            '/api/workspaces/' . $workspace->getId() . '/projects/' . $project->getId() . '/members',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['userId' => (string) $guest->getId()]) ?: '',
        );

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        /** @var array<string, mixed> $data */
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertSame((string) $guest->getId(), $data['id']);
    }

    public function testAssignNonGuestWorkspaceMemberReturns422(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        $membership = UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Owner]);
        $project = ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $membership]);

        $member = UserFactory::createOne();
        UserWorkspaceFactory::createOne(['user' => $member, 'workspace' => $workspace, 'role' => WorkspaceRole::Member]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request(
            'POST',
            '/api/workspaces/' . $workspace->getId() . '/projects/' . $project->getId() . '/members',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['userId' => (string) $member->getId()]) ?: '',
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testAssignDuplicateReturns409(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        $membership = UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Owner]);
        $project = ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $membership, 'status' => ProjectStatus::Active]);

        $guest = UserFactory::createOne();
        UserWorkspaceFactory::createOne(['user' => $guest, 'workspace' => $workspace, 'role' => WorkspaceRole::Guest]);
        UserProjectFactory::createOne(['project' => $project, 'user' => $guest]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request(
            'POST',
            '/api/workspaces/' . $workspace->getId() . '/projects/' . $project->getId() . '/members',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['userId' => (string) $guest->getId()]) ?: '',
        );

        self::assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
    }

    // -------------------------------------------------------------------------
    // REMOVE
    // -------------------------------------------------------------------------

    public function testRemoveUnauthenticatedReturns401(): void
    {
        $client = static::createClient();
        $workspace = WorkspaceFactory::createOne();
        $membership = UserWorkspaceFactory::createOne(['workspace' => $workspace, 'role' => WorkspaceRole::Owner]);
        $project = ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $membership]);
        $guest = UserFactory::createOne();

        $client->request('DELETE', '/api/workspaces/' . $workspace->getId() . '/projects/' . $project->getId() . '/members/' . $guest->getId());

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testRemoveByGuestReturns403(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        $ownerMembership = UserWorkspaceFactory::createOne(['workspace' => $workspace, 'role' => WorkspaceRole::Owner]);
        UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Guest]);
        $project = ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $ownerMembership]);
        $guest = UserFactory::createOne();

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request('DELETE', '/api/workspaces/' . $workspace->getId() . '/projects/' . $project->getId() . '/members/' . $guest->getId());

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testRemoveAssignedGuestReturns204(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        $membership = UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Owner]);
        $project = ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $membership]);

        $guest = UserFactory::createOne();
        UserWorkspaceFactory::createOne(['user' => $guest, 'workspace' => $workspace, 'role' => WorkspaceRole::Guest]);
        UserProjectFactory::createOne(['project' => $project, 'user' => $guest]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request('DELETE', '/api/workspaces/' . $workspace->getId() . '/projects/' . $project->getId() . '/members/' . $guest->getId());

        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
    }

    public function testRemoveNonAssignedUserReturns404(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        $membership = UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Owner]);
        $project = ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $membership]);
        $guest = UserFactory::createOne();

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request('DELETE', '/api/workspaces/' . $workspace->getId() . '/projects/' . $project->getId() . '/members/' . $guest->getId());

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
