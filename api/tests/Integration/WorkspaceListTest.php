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
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

final class WorkspaceListTest extends WebTestCase
{
    use Factories;
    use ResetDatabase;

    private const string EMAIL = 'user@example.com';
    private const string PASSWORD = 'SuperSecurePass123!';

    protected function tearDown(): void
    {
        $apiLimiter = static::getContainer()->get('limiter.api');
        assert($apiLimiter instanceof RateLimiterFactory);
        $apiLimiter->create(self::EMAIL)->reset();

        parent::tearDown();
    }

    public function testUnauthenticatedReturns401(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/workspaces');

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testAuthenticatedUserSeesTheirWorkspaceWithRole(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne(['creator' => $user]);
        UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Owner]);

        $this->loginAs($client);
        $client->request('GET', '/api/workspaces');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        /** @var list<array<string, string>> $data */
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertCount(1, $data);
        self::assertSame($workspace->getName(), $data[0]['name']);
        self::assertSame('owner', $data[0]['role']);
        self::assertArrayHasKey('id', $data[0]);
        self::assertArrayHasKey('createdAt', $data[0]);
    }

    public function testUserInTwoWorkspacesSeesAll(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $ws1 = WorkspaceFactory::createOne();
        $ws2 = WorkspaceFactory::createOne();
        UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $ws1, 'role' => WorkspaceRole::Owner]);
        UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $ws2, 'role' => WorkspaceRole::Member]);

        $this->loginAs($client);
        $client->request('GET', '/api/workspaces');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        /** @var list<array<string, string>> $data */
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertCount(2, $data);
    }

    public function testUserDoesNotSeeOtherUsersWorkspaces(): void
    {
        $client = static::createClient();
        UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $otherUser = UserFactory::createOne();
        $workspace = WorkspaceFactory::createOne();
        UserWorkspaceFactory::createOne(['user' => $otherUser, 'workspace' => $workspace, 'role' => WorkspaceRole::Owner]);

        $this->loginAs($client);
        $client->request('GET', '/api/workspaces');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertSame([], $data);
    }

    public function testUserWithNoWorkspacesReturnsEmptyArray(): void
    {
        $client = static::createClient();
        UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);

        $this->loginAs($client);
        $client->request('GET', '/api/workspaces');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertSame([], $data);
    }

    public function testResponseShapeMatchesSpec(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne(['creator' => $user, 'name' => 'My Project']);
        UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Member]);

        $this->loginAs($client);
        $client->request('GET', '/api/workspaces');

        /** @var list<array<string, string>> $data */
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertArrayHasKey('id', $data[0]);
        self::assertArrayHasKey('name', $data[0]);
        self::assertArrayHasKey('createdAt', $data[0]);
        self::assertArrayHasKey('role', $data[0]);
        self::assertSame('My Project', $data[0]['name']);
        self::assertSame('member', $data[0]['role']);
        self::assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/',
            $data[0]['createdAt'],
        );
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
