<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Enum\WorkspaceRole;
use App\Factory\ProjectFactory;
use App\Factory\TaskFactory;
use App\Factory\UserFactory;
use App\Factory\UserWorkspaceFactory;
use App\Factory\WorkspaceFactory;
use Symfony\Component\HttpFoundation\Response;

final class TaskDeleteTest extends IntegrationTestCase
{
    public function testUnauthenticatedReturns401(): void
    {
        $client = static::createClient();
        $workspace = WorkspaceFactory::createOne();
        $membership = UserWorkspaceFactory::createOne(['workspace' => $workspace, 'role' => WorkspaceRole::Owner]);
        $project = ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $membership]);
        $task = TaskFactory::createOne(['project' => $project, 'creator' => $membership->getUser()]);

        $client->request('DELETE', '/api/workspaces/' . $workspace->getId() . '/projects/' . $project->getId() . '/tasks/' . $task->getId());

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testGuestReturns403(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        $ownerMembership = UserWorkspaceFactory::createOne(['workspace' => $workspace, 'role' => WorkspaceRole::Owner]);
        UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Guest]);
        $project = ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $ownerMembership]);
        $task = TaskFactory::createOne(['project' => $project, 'creator' => $ownerMembership->getUser()]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request('DELETE', '/api/workspaces/' . $workspace->getId() . '/projects/' . $project->getId() . '/tasks/' . $task->getId());

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testMemberNonCreatorReturns403(): void
    {
        $client = static::createClient();
        $member = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        $ownerMembership = UserWorkspaceFactory::createOne(['workspace' => $workspace, 'role' => WorkspaceRole::Owner]);
        UserWorkspaceFactory::createOne(['user' => $member, 'workspace' => $workspace, 'role' => WorkspaceRole::Member]);
        $project = ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $ownerMembership]);
        $task = TaskFactory::createOne(['project' => $project, 'creator' => $ownerMembership->getUser()]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request('DELETE', '/api/workspaces/' . $workspace->getId() . '/projects/' . $project->getId() . '/tasks/' . $task->getId());

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testOwnerCanDeleteAnyTask(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        $ownerMembership = UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Owner]);
        $otherMembership = UserWorkspaceFactory::createOne(['workspace' => $workspace, 'role' => WorkspaceRole::Member]);
        $project = ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $ownerMembership]);
        $task = TaskFactory::createOne(['project' => $project, 'creator' => $otherMembership->getUser()]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request('DELETE', '/api/workspaces/' . $workspace->getId() . '/projects/' . $project->getId() . '/tasks/' . $task->getId());

        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
    }

    public function testCreatorCanDeleteOwnTask(): void
    {
        $client = static::createClient();
        $creator = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        $ownerMembership = UserWorkspaceFactory::createOne(['workspace' => $workspace, 'role' => WorkspaceRole::Owner]);
        UserWorkspaceFactory::createOne(['user' => $creator, 'workspace' => $workspace, 'role' => WorkspaceRole::Member]);
        $project = ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $ownerMembership]);
        $task = TaskFactory::createOne(['project' => $project, 'creator' => $creator]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request('DELETE', '/api/workspaces/' . $workspace->getId() . '/projects/' . $project->getId() . '/tasks/' . $task->getId());

        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
    }

    public function testTaskFromDifferentProjectReturns404(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        $membership = UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Owner]);
        $project = ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $membership]);
        $otherProject = ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $membership]);
        $task = TaskFactory::createOne(['project' => $otherProject, 'creator' => $membership->getUser()]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request('DELETE', '/api/workspaces/' . $workspace->getId() . '/projects/' . $project->getId() . '/tasks/' . $task->getId());

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
