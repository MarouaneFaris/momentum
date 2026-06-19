<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Repository\WorkspaceRepository;
use App\Service\WorkspaceContext;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Uid\Uuid;

final readonly class WorkspaceScopeSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private WorkspaceRepository $workspaceRepository,
        private WorkspaceContext $workspaceContext,
        private EntityManagerInterface $entityManager,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 4], // after routing (priority < 5)
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $filters = $this->entityManager->getFilters();

        $workspaceId = $event->getRequest()->attributes->get('workspaceId');
        if ($workspaceId === null) {
            // Unscoped route: make sure no stale 'workspace' filter survives from a
            // previous request handled by this worker (FrankenPHP reuses the EM).
            if ($filters->isEnabled('workspace')) {
                $filters->disable('workspace');
            }

            return;
        }

        if (!\is_string($workspaceId) || !Uuid::isValid($workspaceId)) {
            throw new NotFoundHttpException(sprintf('Workspace "%s" not found.', $workspaceId));
        }

        $workspace = $this->workspaceRepository->find($workspaceId);
        if ($workspace === null) {
            throw new NotFoundHttpException(sprintf('Workspace "%s" not found.', $workspaceId));
        }

        $this->workspaceContext->set($workspace);

        $filter = $filters->enable('workspace');
        $filter->setParameter('workspaceId', (string) $workspace->getId(), 'string');
    }
}
