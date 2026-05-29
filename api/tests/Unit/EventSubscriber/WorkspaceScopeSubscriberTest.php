<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventSubscriber;

use App\Doctrine\Filter\WorkspaceFilter;
use App\Entity\Workspace;
use App\EventSubscriber\WorkspaceScopeSubscriber;
use App\Repository\WorkspaceRepository;
use App\Service\WorkspaceContext;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\FilterCollection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Uid\Uuid;

final class WorkspaceScopeSubscriberTest extends TestCase
{
    public function testGetSubscribedEventsReturnsPriority4(): void
    {
        $events = WorkspaceScopeSubscriber::getSubscribedEvents();

        self::assertSame([KernelEvents::REQUEST => ['onKernelRequest', 4]], $events);
    }

    public function testNoWorkspaceAttributeSkipsFilter(): void
    {
        $repo = $this->createMock(WorkspaceRepository::class);
        $repo->expects(self::never())->method('find');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::never())->method('getFilters');

        $subscriber = new WorkspaceScopeSubscriber($repo, new WorkspaceContext(), $em);
        $subscriber->onKernelRequest($this->makeEvent(new Request()));
    }

    public function testWorkspaceNotFoundThrows404(): void
    {
        $repo = $this->createMock(WorkspaceRepository::class);
        $repo->expects(self::once())->method('find')->with('missing-id')->willReturn(null);

        $subscriber = new WorkspaceScopeSubscriber(
            $repo,
            new WorkspaceContext(),
            $this->createStub(EntityManagerInterface::class),
        );

        $request = new Request([], [], ['workspaceId' => 'missing-id']);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Workspace "missing-id" not found.');

        $subscriber->onKernelRequest($this->makeEvent($request));
    }

    public function testWorkspaceFoundEnablesFilterAndSetsContext(): void
    {
        $uuid = Uuid::v4();

        $workspace = $this->createStub(Workspace::class);
        $workspace->method('getId')->willReturn($uuid);

        $repo = $this->createMock(WorkspaceRepository::class);
        $repo->expects(self::once())->method('find')->with((string) $uuid)->willReturn($workspace);

        $context = new WorkspaceContext();

        // SQLFilter::setParameter is final; use real WorkspaceFilter with a stub EM
        $filterEm = $this->createStub(EntityManagerInterface::class);
        $filterFilters = $this->createStub(FilterCollection::class);
        $filterEm->method('getFilters')->willReturn($filterFilters);
        $workspaceFilter = new WorkspaceFilter($filterEm);

        $filters = $this->createMock(FilterCollection::class);
        $filters->expects(self::once())->method('enable')->with('workspace')->willReturn($workspaceFilter);

        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('getFilters')->willReturn($filters);

        $subscriber = new WorkspaceScopeSubscriber($repo, $context, $em);
        $request = new Request([], [], ['workspaceId' => (string) $uuid]);
        $subscriber->onKernelRequest($this->makeEvent($request));

        self::assertSame($workspace, $context->get());
    }

    private function makeEvent(Request $request): RequestEvent
    {
        return new RequestEvent(
            $this->createStub(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
        );
    }
}
