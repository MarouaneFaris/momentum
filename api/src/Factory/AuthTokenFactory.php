<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\AuthToken;
use App\Service\AuthTokenManager;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<AuthToken>
 */
final class AuthTokenFactory extends PersistentProxyObjectFactory
{
    #[\Override]
    public static function class(): string
    {
        return AuthToken::class;
    }

    #[\Override]
    protected function defaults(): array
    {
        return [
            'token' => AuthTokenManager::hashToken(bin2hex(random_bytes(32))),
            'user' => UserFactory::new(),
            'expiresAt' => new \DateTimeImmutable('+30 days'),
        ];
    }
}
