<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\User;
use App\Entity\Workspace;
use App\Enum\WorkspaceRole;
use App\Factory\UserFactory;
use App\Factory\UserWorkspaceFactory;
use App\Factory\WorkspaceFactory;
use App\Repository\UserWorkspaceRepository;
use App\Security\WorkspaceMembershipResolver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Guards against the FrankenPHP worker-mode staleness bug: the resolver caches
 * memberships within a request, and that instance is reused across requests in
 * worker mode. If the cache is not cleared between requests, a membership cached
 * before a user is removed keeps granting access afterwards (voter grants -> the
 * workspace/projects controllers then assert on a now-missing row -> 500).
 */
final class WorkspaceMembershipResolverTest extends IntegrationTestCase
{
    public function testResolverIsResettableSoWorkerModeClearsItBetweenRequests(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $resolver = $container->get(WorkspaceMembershipResolver::class);
        $em = $container->get(EntityManagerInterface::class);
        $repo = $container->get(UserWorkspaceRepository::class);
        \assert($resolver instanceof WorkspaceMembershipResolver);
        \assert($em instanceof EntityManagerInterface);
        \assert($repo instanceof UserWorkspaceRepository);

        // Wired into kernel.reset so the worker runtime clears it each request.
        self::assertInstanceOf(ResetInterface::class, $resolver);

        $user = UserFactory::createOne();
        $workspace = WorkspaceFactory::createOne();
        UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Member]);

        $userEntity = $em->getRepository(User::class)->find($user->getId());
        $workspaceEntity = $em->getRepository(Workspace::class)->find($workspace->getId());
        \assert($userEntity instanceof User);
        \assert($workspaceEntity instanceof Workspace);

        // First resolution caches the membership.
        self::assertNotNull($resolver->for($userEntity, $workspaceEntity));

        // User is removed from the workspace (membership row deleted).
        $membership = $repo->findOneBy(['user' => $userEntity, 'workspace' => $workspaceEntity]);
        self::assertNotNull($membership);
        $em->remove($membership);
        $em->flush();

        // Within the same "request" the cache still serves the stale membership.
        self::assertNotNull($resolver->for($userEntity, $workspaceEntity));

        // Simulating the end-of-request reset the worker runtime performs.
        $resolver->reset();

        // Now the resolver reflects reality: no membership -> voter denies -> 403.
        self::assertNull($resolver->for($userEntity, $workspaceEntity));
    }
}
