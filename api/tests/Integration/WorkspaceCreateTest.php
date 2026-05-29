<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\UserWorkspace;
use App\Entity\Workspace;
use App\Enum\WorkspaceRole;
use App\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

final class WorkspaceCreateTest extends WebTestCase
{
    use Factories;
    use ResetDatabase;

    private const string EMAIL = 'user@example.com';
    private const string PASSWORD = 'SuperSecurePass123!';

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
        $this->loginAs($client);

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
        $this->loginAs($client);

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
        $this->loginAs($client);

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
        $this->loginAs($client);

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
        $this->loginAs($client);

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
