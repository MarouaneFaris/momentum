<?php

declare(strict_types=1);

namespace App\Service;

use App\Error\ErrorCode;
use App\Exception\ApiException;
use App\Message\SendVerificationEmail;
use App\Repository\EmailVerificationTokenRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class EmailVerificationService
{
    public function __construct(
        private EmailVerificationTokenRepository $tokenRepository,
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private MessageBusInterface $bus,
        private ClockInterface $clock,
    ) {}

    public function verify(string $rawToken): void
    {
        if ($rawToken === '') {
            throw new ApiException(ErrorCode::EMAIL_TOKEN_INVALID, 'Missing token.', [], Response::HTTP_BAD_REQUEST);
        }

        $tokenHash = AuthTokenManager::hashToken($rawToken);
        $token = $this->tokenRepository->findByHash($tokenHash);

        if ($token === null || $token->isUsed()) {
            throw new ApiException(ErrorCode::EMAIL_TOKEN_INVALID, 'Token is invalid or has already been used.', [], Response::HTTP_BAD_REQUEST);
        }

        $now = $this->clock->now();

        if ($token->getExpiresAt() < $now) {
            throw new ApiException(ErrorCode::EMAIL_TOKEN_EXPIRED, 'Token has expired. Please request a new verification email.', [], Response::HTTP_BAD_REQUEST);
        }

        $user = $token->getUser();
        $token->setUsedAt($now);
        $user->setEmailVerifiedAt($now);
        $this->tokenRepository->invalidatePendingForUser($user);
        $this->entityManager->flush();
    }

    public function resend(string $email): void
    {
        $user = $this->userRepository->findOneBy(['email' => mb_strtolower($email)]);

        if ($user !== null && !$user->isEmailVerified()) {
            $this->bus->dispatch(new SendVerificationEmail((string) $user->getId()));
        }
    }
}
