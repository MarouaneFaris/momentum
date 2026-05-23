<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Service\AuthTokenManager;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::REQUEST, priority: 10)]
final readonly class PreventAuthenticatedAccessListener
{
    private const array GUEST_ONLY_ROUTES = ['api_login', 'api_register'];

    public function __construct(private AuthTokenManager $authTokenManager) {}

    public function __invoke(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (!in_array($request->attributes->get('_route'), self::GUEST_ONLY_ROUTES, true)) {
            return;
        }

        $rawToken = $request->cookies->get(AuthTokenManager::COOKIE_NAME);

        if ($rawToken === null || $this->authTokenManager->findValidToken($rawToken) === null) {
            return;
        }

        $event->setResponse(new JsonResponse(
            ['message' => 'Already authenticated.'],
            Response::HTTP_CONFLICT,
        ));
    }
}
