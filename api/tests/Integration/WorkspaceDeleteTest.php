<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\UserWorkspace;
use App\Enum\WorkspaceRole;
use App\Factory\UserFactory;
use App\Factory\UserWorkspaceFactory;
use App\Factory\WorkspaceFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

final class WorkspaceDeleteTest extends WebTestCase
{
    use Factories;
    use ResetDatabase;

    private const string EMAIL = 'user@example.com';
    private const string PASSWORD = 'SuperSecurePass123!';

    public function testUnauthenticatedReturns401(): void
    {
        $client = static::createClient();
        $workspace = WorkspaceFactory::createOne();

        $client->request('DELETE', '/api/workspaces/' . $workspace->getId());

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testOwnerCanDeleteWorkspaceAndReceives204(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Owner]);

        $this->loginAs($client);
        $workspaceId = $workspace->getId();
        $client->request('DELETE', '/api/workspaces/' . $workspaceId);

        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
    }

    public function testMembershipsAreRemovedAfterDeletion(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        $userWorkspace = UserWorkspaceFactory::createOne([
            'user' => $user,
            'workspace' => $workspace,
            'role' => WorkspaceRole::Owner,
        ]);
        $userWorkspaceId = $userWorkspace->getId();

        $this->loginAs($client);
        $client->request('DELETE', '/api/workspaces/' . $workspace->getId());

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();

        $remaining = $em->getRepository(UserWorkspace::class)->find($userWorkspaceId);
        self::assertNull($remaining);
    }

    public function testMemberCannotDeleteWorkspace(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Member]);

        $this->loginAs($client);
        $client->request('DELETE', '/api/workspaces/' . $workspace->getId());

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testGuestCannotDeleteWorkspace(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Guest]);

        $this->loginAs($client);
        $client->request('DELETE', '/api/workspaces/' . $workspace->getId());

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testNonExistentWorkspaceReturns404(): void
    {
        $client = static::createClient();
        UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);

        $this->loginAs($client);
        $client->request('DELETE', '/api/workspaces/00000000-0000-0000-0000-000000000000');

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    private function loginAs(KernelBrowser $client): void
    {
        $client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => self::EMAIL, 'password' => self::PASSWORD], JSON_THROW_ON_ERROR),
        );
    }
}
