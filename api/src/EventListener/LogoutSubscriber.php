<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Repository\AuthTokenRepository;
use App\Service\AuthTokenManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
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
        #[Autowire(service: 'cache.auth_tokens')]
        private CacheItemPoolInterface $authTokenCache,
        private LoggerInterface $logger,
    ) {}

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [LogoutEvent::class => 'onLogout'];
    }

    public function onLogout(LogoutEvent $event): void
    {
        $request = $event->getRequest();
        $rawToken = $request->cookies->get(AuthTokenManager::COOKIE_NAME);

        if ($rawToken) {
            $authToken = $this->repository->findOneBy([
                'token' => AuthTokenManager::hashToken($rawToken),
            ]);

            if ($authToken) {
                $this->entityManager->remove($authToken);
                $this->entityManager->flush();
            }

            try {
                $this->authTokenCache->deleteItem(AuthTokenManager::tokenCacheKey($rawToken));
            } catch (\Throwable $e) {
                $this->logger->warning('Failed to delete auth token from cache on logout.', ['exception' => $e]);
            }
        }

        $response = new JsonResponse(status: Response::HTTP_NO_CONTENT);
        $response->headers->setCookie(AuthTokenManager::createClearCookie());
        $response->headers->clearCookie('mercureAuthorization', '/.well-known/mercure', null, true, false, Cookie::SAMESITE_STRICT);
        $event->setResponse($response);
    }
}
