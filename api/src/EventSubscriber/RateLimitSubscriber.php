<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final readonly class RateLimitSubscriber implements EventSubscriberInterface
{
    private const array API_LIMITER_EXCLUDED_PATHS = ['/api/login', '/api/logout', '/api/health'];

    public function __construct(
        private RateLimiterFactory $registerLimiter,
        private RateLimiterFactory $apiLimiter,
        private TokenStorageInterface $tokenStorage,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 5],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest() || $event->hasResponse()) {
            return;
        }

        $request = $event->getRequest();
        $path = $request->getPathInfo();

        if ($path === '/api/register') {
            $limiter = $this->registerLimiter->create($request->getClientIp());
            $limit = $limiter->consume(1);

            if (!$limit->isAccepted()) {
                $event->setResponse($this->tooManyRequestsResponse($limit->getRetryAfter()));
            }

            return;
        }

        if (str_starts_with($path, '/api/') && !in_array($path, self::API_LIMITER_EXCLUDED_PATHS, true)) {
            $token = $this->tokenStorage->getToken();
            if ($token === null) {
                return;
            }

            $limiter = $this->apiLimiter->create((string) $token->getUserIdentifier());
            $limit = $limiter->consume(1);

            if (!$limit->isAccepted()) {
                $event->setResponse($this->tooManyRequestsResponse($limit->getRetryAfter()));
            }
        }
    }

    private function tooManyRequestsResponse(\DateTimeImmutable $retryAfter): JsonResponse
    {
        $retryAfterSeconds = max(0, $retryAfter->getTimestamp() - time());

        return new JsonResponse(
            ['error' => 'Too many requests', 'retry_after' => $retryAfterSeconds],
            429,
            ['Retry-After' => $retryAfterSeconds],
        );
    }
}
