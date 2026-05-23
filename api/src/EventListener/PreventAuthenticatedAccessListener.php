<?php

declare(strict_types=1);

namespace App\EventListener;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::REQUEST, priority: 5)]
final readonly class PreventAuthenticatedAccessListener
{
    private const array GUEST_ONLY_ROUTES = ['api_login', 'api_register'];

    public function __construct(private Security $security) {}

    public function __invoke(RequestEvent $event): void
    {
        $route = $event->getRequest()->attributes->get('_route');

        if (in_array($route, self::GUEST_ONLY_ROUTES, true) && $this->security->getUser()) {
            $event->setResponse(new JsonResponse(
                ['message' => 'Already authenticated.'],
                Response::HTTP_CONFLICT,
            ));
        }
    }
}
