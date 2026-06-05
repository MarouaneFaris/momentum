<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\User;
use App\Entity\UserWorkspace;
use App\Entity\Workspace;
use App\Enum\WorkspaceRole;
use App\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;

final class RegistrationTest extends IntegrationTestCase
{
    protected const string EMAIL = 'newuser@example.com';
    private const string NAME = 'Alex Johnson';

    private static int $ipCounter = 10;

    private function nextIp(): string
    {
        return '10.0.0.' . self::$ipCounter++;
    }

    /** @param array<string, string> $payload */
    private function post(KernelBrowser $client, string $ip, array $payload): void
    {
        $factory = static::getContainer()->get('limiter.register');
        assert($factory instanceof RateLimiterFactory);
        $factory->create($ip)->reset();

        $client->request(
            'POST',
            '/api/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'REMOTE_ADDR' => $ip],
            json_encode($payload, JSON_THROW_ON_ERROR),
        );
    }

    public function testValidPayloadReturns201(): void
    {
        $client = static::createClient();
        $this->post($client, $this->nextIp(), ['name' => self::NAME, 'email' => self::EMAIL, 'password' => self::PASSWORD]);

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
    }

    public function testUserRowExistsInDbAfterRegistration(): void
    {
        $client = static::createClient();
        $this->post($client, $this->nextIp(), ['name' => self::NAME, 'email' => self::EMAIL, 'password' => self::PASSWORD]);

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $user = $em->getRepository(User::class)->findOneBy(['email' => self::EMAIL]);

        self::assertNotNull($user);
        self::assertSame(self::EMAIL, $user->getEmail());
    }

    public function testStoredPasswordIsHashed(): void
    {
        $client = static::createClient();
        $this->post($client, $this->nextIp(), ['name' => self::NAME, 'email' => self::EMAIL, 'password' => self::PASSWORD]);

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $user = $em->getRepository(User::class)->findOneBy(['email' => self::EMAIL]);

        self::assertNotNull($user);
        self::assertNotSame(self::PASSWORD, $user->getPassword());
        self::assertMatchesRegularExpression('/^\$2[aby]|\$argon/', (string) $user->getPassword());
    }

    public function testDuplicateEmailReturns201(): void
    {
        $client = static::createClient();
        UserFactory::createOne(['email' => self::EMAIL]);
        $this->post($client, $this->nextIp(), ['name' => self::NAME, 'email' => self::EMAIL, 'password' => self::PASSWORD]);

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
    }

    public function testMissingNameReturns422(): void
    {
        $client = static::createClient();
        $this->post($client, $this->nextIp(), ['email' => self::EMAIL, 'password' => self::PASSWORD]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testMissingEmailReturns422(): void
    {
        $client = static::createClient();
        $this->post($client, $this->nextIp(), ['password' => self::PASSWORD]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testMissingPasswordReturns422(): void
    {
        $client = static::createClient();
        $this->post($client, $this->nextIp(), ['email' => self::EMAIL]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testInvalidEmailReturns422(): void
    {
        $client = static::createClient();
        $this->post($client, $this->nextIp(), ['email' => 'not-an-email', 'password' => self::PASSWORD]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testShortPasswordReturns422(): void
    {
        $client = static::createClient();
        $this->post($client, $this->nextIp(), ['email' => self::EMAIL, 'password' => 'short']);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testNewUserHasExactlyOneWorkspace(): void
    {
        $client = static::createClient();
        $userWorkspaces = $this->registerAndGetUserWorkspaces($client);

        self::assertCount(1, $userWorkspaces);

        $workspace = $userWorkspaces[0]->getWorkspace();
        self::assertInstanceOf(Workspace::class, $workspace);
        self::assertSame('My workspace', $workspace->getName());
    }

    public function testNewUserWorkspaceRoleIsOwner(): void
    {
        $client = static::createClient();
        $userWorkspaces = $this->registerAndGetUserWorkspaces($client);

        self::assertCount(1, $userWorkspaces);
        self::assertSame(WorkspaceRole::Owner, $userWorkspaces[0]->getRole());
    }

    /** @return list<UserWorkspace> */
    private function registerAndGetUserWorkspaces(KernelBrowser $client): array
    {
        $this->post($client, $this->nextIp(), ['name' => self::NAME, 'email' => self::EMAIL, 'password' => self::PASSWORD]);

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $user = $em->getRepository(User::class)->findOneBy(['email' => self::EMAIL]);

        self::assertNotNull($user);

        return array_values($em->getRepository(UserWorkspace::class)->findBy(['user' => $user]));
    }

    public function testDuplicateEmailDoesNotCreateWorkspace(): void
    {
        $client = static::createClient();
        UserFactory::createOne(['email' => self::EMAIL]);
        $this->post($client, $this->nextIp(), ['name' => self::NAME, 'email' => self::EMAIL, 'password' => self::PASSWORD]);

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $user = $em->getRepository(User::class)->findOneBy(['email' => self::EMAIL]);

        self::assertNotNull($user);
        self::assertCount(0, $em->getRepository(UserWorkspace::class)->findBy(['user' => $user]));
    }
}
