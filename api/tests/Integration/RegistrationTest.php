<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\User;
use App\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

final class RegistrationTest extends WebTestCase
{
    use Factories;
    use ResetDatabase;

    private const string EMAIL = 'newuser@example.com';
    private const string PASSWORD = 'SuperSecurePass123!';

    private static int $ipCounter = 10;

    private function nextIp(): string
    {
        return '10.0.0.' . self::$ipCounter++;
    }

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
        $this->post($client, $this->nextIp(), ['email' => self::EMAIL, 'password' => self::PASSWORD]);

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
    }

    public function testUserRowExistsInDbAfterRegistration(): void
    {
        $client = static::createClient();
        $this->post($client, $this->nextIp(), ['email' => self::EMAIL, 'password' => self::PASSWORD]);

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $user = $em->getRepository(User::class)->findOneBy(['email' => self::EMAIL]);

        self::assertNotNull($user);
        self::assertSame(self::EMAIL, $user->getEmail());
    }

    public function testStoredPasswordIsHashed(): void
    {
        $client = static::createClient();
        $this->post($client, $this->nextIp(), ['email' => self::EMAIL, 'password' => self::PASSWORD]);

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
        $this->post($client, $this->nextIp(), ['email' => self::EMAIL, 'password' => self::PASSWORD]);

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
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
}
