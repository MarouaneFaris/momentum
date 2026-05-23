<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\AuthToken;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\Cookie;

final readonly class AuthTokenManager
{
    public const string COOKIE_NAME = 'auth_token';

    public function __construct(
        private ClockInterface $clock,
        private EntityManagerInterface $entityManager,
    ) {}

    public static function hashToken(string $rawToken): string
    {
        return hash('sha256', $rawToken);
    }

    public static function createClearCookie(): Cookie
    {
        return Cookie::create(
            name: self::COOKIE_NAME,
            secure: true,
            httpOnly: true,
            sameSite: Cookie::SAMESITE_STRICT,
        );
    }

    /**
     * @return array{token: string, expiresAt: \DateTimeImmutable}
     */
    public function createToken(User $user): array
    {
        $now = $this->clock->now();
        $token = bin2hex(random_bytes(32));

        $authToken = new AuthToken()
            ->setToken(self::hashToken($token))
            ->setUser($user)
            ->setCreatedAt($now)
            ->setExpiresAt($now->modify('+30 days'));

        $this->entityManager->persist($authToken);
        $this->entityManager->flush();

        return ['token' => $token, 'expiresAt' => $authToken->getExpiresAt()];
    }
}
