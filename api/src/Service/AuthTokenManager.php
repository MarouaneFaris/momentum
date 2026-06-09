<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\AuthToken;
use App\Entity\User;
use App\Repository\AuthTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\Uid\Uuid;

final readonly class AuthTokenManager
{
    public const string COOKIE_NAME = 'auth_token';

    private const string CACHE_KEY_PREFIX = 'auth_token_';

    public function __construct(
        private ClockInterface $clock,
        private EntityManagerInterface $entityManager,
        private AuthTokenRepository $repository,
        #[Autowire(service: 'cache.auth_tokens')]
        private CacheItemPoolInterface $authTokenCache,
    ) {}

    public function findUserByToken(string $rawToken): ?User
    {
        $cacheItem = $this->authTokenCache->getItem(self::tokenCacheKey($rawToken));
        if ($cacheItem->isHit()) {
            return $this->entityManager->find(User::class, Uuid::fromString($cacheItem->get()));
        }

        $authToken = $this->repository->findOneBy(['token' => self::hashToken($rawToken)]);
        if (!$authToken || $authToken->getExpiresAt() < $this->clock->now()) {
            return null;
        }

        $userId = $authToken->getUser()->getId();
        if ($userId !== null) {
            $ttl = $authToken->getExpiresAt()->getTimestamp() - $this->clock->now()->getTimestamp();
            $cacheItem->set($userId->toRfc4122());
            $cacheItem->expiresAfter(max(1, $ttl));
            $this->authTokenCache->save($cacheItem);
        }

        return $authToken->getUser();
    }

    public function findValidToken(string $rawToken): ?AuthToken
    {
        $authToken = $this->repository->findOneBy(['token' => self::hashToken($rawToken)]);

        if (!$authToken || $authToken->getExpiresAt() < $this->clock->now()) {
            return null;
        }

        return $authToken;
    }

    public static function hashToken(string $rawToken): string
    {
        return hash('sha256', $rawToken);
    }

    public static function tokenCacheKey(string $rawToken): string
    {
        return self::CACHE_KEY_PREFIX . self::hashToken($rawToken);
    }

    public static function createClearCookie(): Cookie
    {
        return Cookie::create(
            name: self::COOKIE_NAME,
            expire: 1,
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
