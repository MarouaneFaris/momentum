<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

abstract class IntegrationTestCase extends WebTestCase
{
    use Factories;
    use LoginAsTrait;
    use ResetDatabase;

    protected const string EMAIL = 'user@example.com';
    protected const string PASSWORD = 'SuperSecurePass123!';

    protected function tearDown(): void
    {
        $apiLimiter = static::getContainer()->get('limiter.api');
        assert($apiLimiter instanceof RateLimiterFactory);
        $apiLimiter->create(static::EMAIL)->reset();

        parent::tearDown();
    }
}
