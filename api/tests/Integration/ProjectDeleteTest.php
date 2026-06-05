<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Enum\WorkspaceRole;
use App\Factory\ProjectFactory;
use App\Factory\UserFactory;
use App\Factory\UserWorkspaceFactory;
use App\Factory\WorkspaceFactory;
use Symfony\Component\HttpFoundation\Response;

final class ProjectDeleteTest extends IntegrationTestCase
{
    public function testUnauthenticatedReturns401(): void
    {
        $client = static::createClient();
        $workspace = WorkspaceFactory::createOne();
        $ownerMembership = UserWorkspaceFactory::createOne(['workspace' => $workspace, 'role' => WorkspaceRole::Owner]);
        $project = ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $ownerMembership]);

        $client->request('DELETE', '/api/workspaces/' . $workspace->getId() . '/projects/' . $project->getId());

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testNonMemberReturns403(): void
    {
        $client = static::createClient();
        UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        $ownerMembership = UserWorkspaceFactory::createOne(['workspace' => $workspace, 'role' => WorkspaceRole::Owner]);
        $project = ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $ownerMembership]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request('DELETE', '/api/workspaces/' . $workspace->getId() . '/projects/' . $project->getId());

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testMemberNonOwnerReturns403(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $otherUser = UserFactory::createOne();
        $workspace = WorkspaceFactory::createOne();
        UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Member]);
        $otherMembership = UserWorkspaceFactory::createOne(['user' => $otherUser, 'workspace' => $workspace, 'role' => WorkspaceRole::Member]);
        $project = ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $otherMembership]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request('DELETE', '/api/workspaces/' . $workspace->getId() . '/projects/' . $project->getId());

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testOwnerCanDeleteAnyProject(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $otherUser = UserFactory::createOne();
        $workspace = WorkspaceFactory::createOne();
        UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Owner]);
        $otherMembership = UserWorkspaceFactory::createOne(['user' => $otherUser, 'workspace' => $workspace, 'role' => WorkspaceRole::Member]);
        $project = ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $otherMembership]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request('DELETE', '/api/workspaces/' . $workspace->getId() . '/projects/' . $project->getId());

        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
    }

    public function testMemberCanDeleteOwnProject(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        $membership = UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Member]);
        $project = ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $membership]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request('DELETE', '/api/workspaces/' . $workspace->getId() . '/projects/' . $project->getId());

        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
    }

    public function testProjectNotFoundReturns404(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Owner]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request('DELETE', '/api/workspaces/' . $workspace->getId() . '/projects/00000000-0000-0000-0000-000000000000');

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
