<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\AuthToken;
use App\Entity\User;
use App\Enum\WorkspaceRole;
use App\Factory\AuthTokenFactory;
use App\Factory\UserFactory;
use App\Factory\UserWorkspaceFactory;
use App\Factory\WorkspaceFactory;
use App\Repository\AuthTokenRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

final class WorkspaceScopeTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    public function testUserRepositoryIsolatesWorkspaceA(): void
    {
        $workspaceA = WorkspaceFactory::createOne();
        $workspaceB = WorkspaceFactory::createOne();

        $userInA = UserFactory::createOne();
        $userInB = UserFactory::createOne();

        UserWorkspaceFactory::createOne(['user' => $userInA, 'workspace' => $workspaceA, 'role' => WorkspaceRole::Member]);
        UserWorkspaceFactory::createOne(['user' => $userInB, 'workspace' => $workspaceB, 'role' => WorkspaceRole::Member]);

        /** @var UserRepository $repo */
        $repo = static::getContainer()->get(UserRepository::class);

        $results = $repo->findByWorkspace($workspaceA);

        self::assertCount(1, $results);
        assert($results[0] instanceof User);
        self::assertSame($userInA->getEmail(), $results[0]->getEmail());
    }

    public function testUserRepositoryExcludesUsersNotInWorkspace(): void
    {
        $workspace = WorkspaceFactory::createOne();
        UserFactory::createOne();

        /** @var UserRepository $repo */
        $repo = static::getContainer()->get(UserRepository::class);

        self::assertCount(0, $repo->findByWorkspace($workspace));
    }

    public function testAuthTokenRepositoryIsolatesWorkspaceA(): void
    {
        $workspaceA = WorkspaceFactory::createOne();
        $workspaceB = WorkspaceFactory::createOne();

        $userInA = UserFactory::createOne();
        $userInB = UserFactory::createOne();

        UserWorkspaceFactory::createOne(['user' => $userInA, 'workspace' => $workspaceA, 'role' => WorkspaceRole::Member]);
        UserWorkspaceFactory::createOne(['user' => $userInB, 'workspace' => $workspaceB, 'role' => WorkspaceRole::Member]);

        $tokenInA = AuthTokenFactory::createOne(['user' => $userInA]);
        AuthTokenFactory::createOne(['user' => $userInB]);

        /** @var AuthTokenRepository $repo */
        $repo = static::getContainer()->get(AuthTokenRepository::class);

        $results = $repo->findByWorkspace($workspaceA);

        self::assertCount(1, $results);
        assert($results[0] instanceof AuthToken);
        self::assertSame($tokenInA->getToken(), $results[0]->getToken());
    }

    public function testAuthTokenRepositoryExcludesTokensNotInWorkspace(): void
    {
        $workspace = WorkspaceFactory::createOne();
        AuthTokenFactory::createOne();

        /** @var AuthTokenRepository $repo */
        $repo = static::getContainer()->get(AuthTokenRepository::class);

        self::assertCount(0, $repo->findByWorkspace($workspace));
    }
}
