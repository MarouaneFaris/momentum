<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Repository\AuthTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Event\LogoutEvent;

final readonly class LogoutSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private AuthTokenRepository $repository,
        private EntityManagerInterface $entityManager,
    ) {}

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [LogoutEvent::class => 'onLogout'];
    }

    public function onLogout(LogoutEvent $event): void
    {
        $request = $event->getRequest();
        $token = $request->cookies->get('auth_token');

        if ($token) {
            $authToken = $this->repository->findOneBy(['token' => $token]);

            if ($authToken) {
                $this->entityManager->remove($authToken);
                $this->entityManager->flush();
            }
        }

        $response = new JsonResponse(status: Response::HTTP_NO_CONTENT);
        $response->headers->clearCookie('auth_token', secure: true, httpOnly: true, sameSite: Cookie::SAMESITE_STRICT);
        $event->setResponse($response);
    }
}
