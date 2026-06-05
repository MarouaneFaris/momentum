<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Enum\WorkspaceRole;
use App\Factory\UserFactory;
use App\Factory\UserWorkspaceFactory;
use App\Factory\WorkspaceFactory;
use Symfony\Component\HttpFoundation\Response;

final class MemberManagementTest extends IntegrationTestCase
{
    protected const string EMAIL = 'owner@example.com';

    // — GET /api/workspaces/{workspaceId}/members ——————————————————————————————

    public function testListMembersReturns401WhenUnauthenticated(): void
    {
        $client = static::createClient();
        $workspace = WorkspaceFactory::createOne();

        $client->request('GET', '/api/workspaces/' . $workspace->getId() . '/members');

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testListMembersReturns200WithMemberArray(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Owner]);
        UserWorkspaceFactory::createOne(['workspace' => $workspace, 'role' => WorkspaceRole::Member]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request('GET', '/api/workspaces/' . $workspace->getId() . '/members');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        /** @var list<array<string, mixed>> $data */
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertCount(2, $data);
        self::assertArrayHasKey('id', $data[0]);
        self::assertArrayHasKey('name', $data[0]);
        self::assertArrayHasKey('email', $data[0]);
        self::assertArrayHasKey('role', $data[0]);
        self::assertArrayHasKey('joinedAt', $data[0]);
    }

    // — PATCH /api/workspaces/{workspaceId}/members/{userId} ——————————————————

    public function testChangeRoleReturns401WhenUnauthenticated(): void
    {
        $client = static::createClient();
        $workspace = WorkspaceFactory::createOne();
        $target = UserFactory::createOne();

        $client->request(
            'PATCH',
            '/api/workspaces/' . $workspace->getId() . '/members/' . $target->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['role' => 'guest'], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testChangeRoleReturns403WhenMember(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Member]);
        $target = UserFactory::createOne();
        UserWorkspaceFactory::createOne(['user' => $target, 'workspace' => $workspace, 'role' => WorkspaceRole::Member]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request(
            'PATCH',
            '/api/workspaces/' . $workspace->getId() . '/members/' . $target->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['role' => 'guest'], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testChangeRoleReturns422WhenTargetRoleIsOwner(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Owner]);
        $target = UserFactory::createOne();
        UserWorkspaceFactory::createOne(['user' => $target, 'workspace' => $workspace, 'role' => WorkspaceRole::Member]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request(
            'PATCH',
            '/api/workspaces/' . $workspace->getId() . '/members/' . $target->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['role' => 'owner'], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testChangeRoleReturns400WhenOwnerChangesOwnRole(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Owner]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request(
            'PATCH',
            '/api/workspaces/' . $workspace->getId() . '/members/' . $user->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['role' => 'member'], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testChangeRoleReturns200WithUpdatedMember(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Owner]);
        $target = UserFactory::createOne();
        UserWorkspaceFactory::createOne(['user' => $target, 'workspace' => $workspace, 'role' => WorkspaceRole::Member]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request(
            'PATCH',
            '/api/workspaces/' . $workspace->getId() . '/members/' . $target->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['role' => 'guest'], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        /** @var array<string, mixed> $data */
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertSame('guest', $data['role']);
    }

    // — DELETE /api/workspaces/{workspaceId}/members/{userId} —————————————————

    public function testRemoveMemberReturns401WhenUnauthenticated(): void
    {
        $client = static::createClient();
        $workspace = WorkspaceFactory::createOne();
        $target = UserFactory::createOne();

        $client->request('DELETE', '/api/workspaces/' . $workspace->getId() . '/members/' . $target->getId());

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testRemoveMemberReturns403WhenMember(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Member]);
        $target = UserFactory::createOne();
        UserWorkspaceFactory::createOne(['user' => $target, 'workspace' => $workspace, 'role' => WorkspaceRole::Member]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request('DELETE', '/api/workspaces/' . $workspace->getId() . '/members/' . $target->getId());

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testRemoveMemberReturns400WhenTargetIsOwner(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Owner]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request('DELETE', '/api/workspaces/' . $workspace->getId() . '/members/' . $user->getId());

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testRemoveMemberReturns204(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Owner]);
        $target = UserFactory::createOne();
        UserWorkspaceFactory::createOne(['user' => $target, 'workspace' => $workspace, 'role' => WorkspaceRole::Member]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request('DELETE', '/api/workspaces/' . $workspace->getId() . '/members/' . $target->getId());

        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
    }

    // — DELETE /api/workspaces/{workspaceId}/members/me ——————————————————————

    public function testLeaveWorkspaceReturns401WhenUnauthenticated(): void
    {
        $client = static::createClient();
        $workspace = WorkspaceFactory::createOne();

        $client->request('DELETE', '/api/workspaces/' . $workspace->getId() . '/members/me');

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testLeaveWorkspaceReturns403WhenOwner(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Owner]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request('DELETE', '/api/workspaces/' . $workspace->getId() . '/members/me');

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testLeaveWorkspaceReturns204ForMember(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Member]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request('DELETE', '/api/workspaces/' . $workspace->getId() . '/members/me');

        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
    }
}
