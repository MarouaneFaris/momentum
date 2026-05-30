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

final class WorkspaceShowTest extends WebTestCase
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

        $this->loginAs($client);
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

        $this->loginAs($client);
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

        $this->loginAs($client);
        $client->request('GET', '/api/workspaces/' . $workspace->getId());

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
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
