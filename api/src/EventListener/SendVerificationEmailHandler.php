<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\EmailVerificationToken;
use App\Entity\User;
use App\Message\SendVerificationEmail;
use App\Repository\EmailVerificationTokenRepository;
use App\Service\AuthTokenManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class SendVerificationEmailHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EmailVerificationTokenRepository $tokenRepository,
        private MailerInterface $mailer,
        #[Autowire('%env(FRONTEND_URL)%')]
        private string $frontendUrl,
    ) {}

    public function __invoke(SendVerificationEmail $message): void
    {
        $user = $this->entityManager->find(User::class, $message->userId);
        if (!$user instanceof User || $user->isEmailVerified()) {
            return;
        }

        $rawToken = bin2hex(random_bytes(32));
        $tokenHash = AuthTokenManager::hashToken($rawToken);

        $this->entityManager->wrapInTransaction(function () use ($user, $tokenHash): void {
            $this->tokenRepository->invalidatePendingForUser($user);

            $token = new EmailVerificationToken();
            $token->setUser($user);
            $token->setTokenHash($tokenHash);
            $token->setExpiresAt(new \DateTimeImmutable('+24 hours'));

            $this->entityManager->persist($token);
            $this->entityManager->flush();
        });

        $verificationUrl = rtrim($this->frontendUrl, '/') . '/verify-email?token=' . $rawToken;

        $email = (new TemplatedEmail())
            ->to($user->getEmail())
            ->subject('Verify your Momentum account')
            ->htmlTemplate('emails/verify_email.html.twig')
            ->context([
                'name' => $user->getName(),
                'verification_url' => $verificationUrl,
            ]);

        $this->mailer->send($email);
    }
}
