<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\UserWorkspace;
use App\Entity\Workspace;
use App\Enum\WorkspaceRole;
use App\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

final class WorkspaceCreateTest extends WebTestCase
{
    use Factories;
    use LoginAsTrait;
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
        $client->request(
            'POST',
            '/api/workspaces',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['name' => 'My Team'], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testValidPayloadCreatesWorkspaceAndReturns201(): void
    {
        $client = static::createClient();
        UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $this->loginAs($client, self::EMAIL, self::PASSWORD);

        $client->request(
            'POST',
            '/api/workspaces',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['name' => 'My Team'], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        /** @var array<string, string> $data */
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertSame('My Team', $data['name']);
        self::assertSame('owner', $data['role']);
        self::assertArrayHasKey('id', $data);
        self::assertArrayHasKey('createdAt', $data);
    }

    public function testCreatedUserWorkspaceHasOwnerRole(): void
    {
        $client = static::createClient();
        UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $this->loginAs($client, self::EMAIL, self::PASSWORD);

        $client->request(
            'POST',
            '/api/workspaces',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['name' => 'My Team'], JSON_THROW_ON_ERROR),
        );

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $workspace = $em->getRepository(Workspace::class)->findOneBy(['name' => 'My Team']);
        self::assertNotNull($workspace);

        $userWorkspace = $em->getRepository(UserWorkspace::class)->findOneBy(['workspace' => $workspace]);
        self::assertNotNull($userWorkspace);
        self::assertSame(WorkspaceRole::Owner, $userWorkspace->getRole());
    }

    public function testMissingNameReturns422(): void
    {
        $client = static::createClient();
        UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $this->loginAs($client, self::EMAIL, self::PASSWORD);

        $client->request(
            'POST',
            '/api/workspaces',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testBlankNameReturns422(): void
    {
        $client = static::createClient();
        UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $this->loginAs($client, self::EMAIL, self::PASSWORD);

        $client->request(
            'POST',
            '/api/workspaces',
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
        UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $this->loginAs($client, self::EMAIL, self::PASSWORD);

        $client->request(
            'POST',
            '/api/workspaces',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['name' => str_repeat('a', 65)], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testNameAt64CharsReturns201(): void
    {
        $client = static::createClient();
        UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $this->loginAs($client, self::EMAIL, self::PASSWORD);

        $client->request(
            'POST',
            '/api/workspaces',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['name' => str_repeat('a', 64)], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
    }
}
