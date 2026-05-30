<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Enum\WorkspaceRole;
use App\Factory\UserFactory;
use App\Factory\UserWorkspaceFactory;
use App\Factory\WorkspaceFactory;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

final class WorkspaceRenameTest extends WebTestCase
{
    use Factories;
    use ResetDatabase;

    private const string EMAIL = 'user@example.com';
    private const string PASSWORD = 'SuperSecurePass123!';

    public function testUnauthenticatedReturns401(): void
    {
        $client = static::createClient();
        $workspace = WorkspaceFactory::createOne();

        $client->request(
            'PATCH',
            '/api/workspaces/' . $workspace->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['name' => 'New Name'], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testOwnerCanRenameWorkspace(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne(['name' => 'Old Name']);
        UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Owner]);

        $this->loginAs($client);
        $client->request(
            'PATCH',
            '/api/workspaces/' . $workspace->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['name' => 'New Name'], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        /** @var array<string, string> $data */
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertSame('New Name', $data['name']);
        self::assertSame('owner', $data['role']);
    }

    public function testMemberCannotRenameWorkspace(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Member]);

        $this->loginAs($client);
        $client->request(
            'PATCH',
            '/api/workspaces/' . $workspace->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['name' => 'New Name'], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testGuestCannotRenameWorkspace(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Guest]);

        $this->loginAs($client);
        $client->request(
            'PATCH',
            '/api/workspaces/' . $workspace->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['name' => 'New Name'], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testBlankNameReturns422(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Owner]);

        $this->loginAs($client);
        $client->request(
            'PATCH',
            '/api/workspaces/' . $workspace->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['name' => ''], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testNameOver64CharsReturns422(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Owner]);

        $this->loginAs($client);
        $client->request(
            'PATCH',
            '/api/workspaces/' . $workspace->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['name' => str_repeat('a', 65)], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    private function loginAs(KernelBrowser $client): void
    {
        $client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => self::EMAIL, 'password' => self::PASSWORD], JSON_THROW_ON_ERROR),
        );
    }
}
