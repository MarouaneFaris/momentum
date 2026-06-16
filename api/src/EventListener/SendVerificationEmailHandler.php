<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\EmailVerificationToken;
use App\Entity\User;
use App\Message\SendVerificationEmail;
use App\Repository\EmailVerificationTokenRepository;
use App\Service\AuthTokenManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Email;
use Twig\Environment;

#[AsMessageHandler]
final readonly class SendVerificationEmailHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EmailVerificationTokenRepository $tokenRepository,
        private MailerInterface $mailer,
        private Environment $twig,
        #[Autowire('%env(DEFAULT_URI)%')]
        private string $appUrl,
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

        $verificationUrl = rtrim($this->appUrl, '/') . '/verify-email?token=' . $rawToken;

        $html = $this->twig->render('emails/verify_email.html.twig', [
            'name' => $user->getName(),
            'verification_url' => $verificationUrl,
        ]);

        $plaintext = "Hi {$user->getName()},\n\nPlease verify your Momentum account by visiting:\n{$verificationUrl}\n\nThis link expires in 24 hours.\n\nIf you didn't create an account, you can safely ignore this email.";

        $email = (new Email())
            ->to($user->getEmail())
            ->subject('Verify your Momentum account')
            ->html($html)
            ->text($plaintext);

        $this->mailer->send($email);
    }
}
