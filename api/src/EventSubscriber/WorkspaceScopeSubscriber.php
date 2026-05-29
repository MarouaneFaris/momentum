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

        $workspaceId = $event->getRequest()->attributes->get('workspaceId');
        if ($workspaceId === null) {
            return;
        }

        $workspace = $this->workspaceRepository->find($workspaceId);
        if ($workspace === null) {
            throw new NotFoundHttpException(sprintf('Workspace "%s" not found.', $workspaceId));
        }

        $this->workspaceContext->set($workspace);

        $filter = $this->entityManager->getFilters()->enable('workspace');
        $filter->setParameter('workspaceId', (string) $workspace->getId(), 'string');
    }
}
