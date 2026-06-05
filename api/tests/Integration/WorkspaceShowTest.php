<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Enum\WorkspaceRole;
use App\Factory\UserFactory;
use App\Factory\UserWorkspaceFactory;
use App\Factory\WorkspaceFactory;
use Symfony\Component\HttpFoundation\Response;

final class WorkspaceShowTest extends IntegrationTestCase
{
    public function testUnauthenticatedReturns401(): void
    {
        $client = static::createClient();
        $workspace = WorkspaceFactory::createOne();

        $client->request('GET', '/api/workspaces/' . $workspace->getId());

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testMemberCanViewWorkspace(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne(['name' => 'Shared Space']);
        UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Member]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request('GET', '/api/workspaces/' . $workspace->getId());

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        /** @var array<string, string> $data */
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertSame('Shared Space', $data['name']);
        self::assertSame('member', $data['role']);
        self::assertArrayHasKey('id', $data);
        self::assertArrayHasKey('createdAt', $data);
    }

    public function testOwnerCanViewWorkspace(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Owner]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request('GET', '/api/workspaces/' . $workspace->getId());

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        /** @var array<string, string> $data */
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertSame('owner', $data['role']);
    }

    public function testNonMemberReturns403(): void
    {
        $client = static::createClient();
        UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request('GET', '/api/workspaces/' . $workspace->getId());

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }
}
