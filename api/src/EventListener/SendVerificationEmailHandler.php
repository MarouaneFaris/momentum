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

        $this->tokenRepository->invalidatePendingForUser($user);

        $rawToken = bin2hex(random_bytes(32));
        $tokenHash = AuthTokenManager::hashToken($rawToken);

        $token = new EmailVerificationToken();
        $token->setUser($user);
        $token->setTokenHash($tokenHash);
        $token->setExpiresAt(new \DateTimeImmutable('+24 hours'));

        $this->entityManager->persist($token);
        $this->entityManager->flush();

        $verificationUrl = rtrim($this->appUrl, '/') . '/verify-email?token=' . $rawToken;

        $html = $this->twig->render('emails/verify_email.html.twig', [
            'name' => $user->getName(),
            'verification_url' => $verificationUrl,
        ]);

        $email = (new Email())
            ->to($user->getEmail())
            ->subject('Verify your Momentum account')
            ->html($html);

        $this->mailer->send($email);
    }
}
