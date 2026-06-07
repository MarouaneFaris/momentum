<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class DevService
{
    public function __construct(
        #[Autowire('%kernel.environment%')] private string $appEnv,
        private UserRepository $userRepository,
        private AuthTokenManager $tokenManager,
    ) {}

    public function ensureDevEnvironment(): void
    {
        if (!in_array($this->appEnv, ['dev', 'test'], true)) {
            throw new NotFoundHttpException();
        }
    }

    /**
     * @return list<array{id: string, email: string}>
     */
    public function getUsers(): array
    {
        return array_map(
            fn (User $user) => ['id' => (string) $user->getId(), 'email' => $user->getEmail()],
            $this->userRepository->findAll(),
        );
    }

    /**
     * @return array{user: User, token: string, expiresAt: \DateTimeImmutable}
     */
    public function loginAs(string $email): array
    {
        $user = $this->userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            throw new NotFoundHttpException('User not found');
        }

        $result = $this->tokenManager->createToken($user);

        return ['user' => $user, 'token' => $result['token'], 'expiresAt' => $result['expiresAt']];
    }
}
