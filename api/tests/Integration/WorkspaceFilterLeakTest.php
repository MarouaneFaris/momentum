<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\Workspace;
use App\Enum\ProjectStatus;
use App\Enum\WorkspaceRole;
use App\Factory\ProjectFactory;
use App\Factory\UserFactory;
use App\Factory\UserWorkspaceFactory;
use App\Factory\WorkspaceFactory;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * WorkspaceScopeSubscriber enables the Doctrine 'workspace' SQL filter for routes
 * carrying {workspaceId} but never disables it. Under FrankenPHP worker mode the
 * EntityManager (and its FilterCollection) is reused across requests, so the filter
 * stays enabled with a stale workspaceId on the next request that has no workspaceId.
 *
 * disableReboot() simulates that reuse: the kernel/container/EM persist across the
 * two requests below, exactly like a single worker handling them in sequence.
 */
final class WorkspaceFilterLeakTest extends IntegrationTestCase
{
    public function testWorkspaceFilterDoesNotLeakIntoUnscopedRequest(): void
    {
        $client = static::createClient();
        $client->disableReboot();

        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $wsA = WorkspaceFactory::createOne(['name' => 'A']);
        $wsB = WorkspaceFactory::createOne(['name' => 'B']);
        $mA = UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $wsA, 'role' => WorkspaceRole::Owner]);
        $mB = UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $wsB, 'role' => WorkspaceRole::Owner]);
        ProjectFactory::createOne(['workspace' => $wsA, 'owner' => $mA, 'status' => ProjectStatus::Active]);
        ProjectFactory::createOne(['workspace' => $wsB, 'owner' => $mB, 'status' => ProjectStatus::Active]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);

        // Scoped request: enables the 'workspace' filter with param = wsA.
        $client->request('GET', '/api/workspaces/' . $wsA->getId() . '/projects');
        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        // Unscoped request (no workspaceId): must NOT carry the stale wsA filter.
        $client->request('GET', '/api/workspaces');
        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $em = static::getContainer()->get(EntityManagerInterface::class);
        \assert($em instanceof EntityManagerInterface);

        // After an unscoped request the workspace filter must be off.
        self::assertFalse(
            $em->getFilters()->isEnabled('workspace'),
            'workspace filter leaked (still enabled) after an unscoped request',
        );

        // Concrete consequence: querying wsB projects must return wsB data, not be
        // silently scoped to the stale wsA. Proves the leak corrupts results.
        $repo = static::getContainer()->get(ProjectRepository::class);
        \assert($repo instanceof ProjectRepository);
        $wsBEntity = $em->getRepository(Workspace::class)->find($wsB->getId());
        $projects = $repo->findBy(['workspace' => $wsBEntity]);
        self::assertCount(1, $projects, 'wsB project query was scoped to the stale wsA filter');
    }
}
