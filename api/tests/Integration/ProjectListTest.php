<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Enum\ProjectStatus;
use App\Enum\WorkspaceRole;
use App\Factory\ProjectFactory;
use App\Factory\UserFactory;
use App\Factory\UserProjectFactory;
use App\Factory\UserWorkspaceFactory;
use App\Factory\WorkspaceFactory;
use Symfony\Component\HttpFoundation\Response;

final class ProjectListTest extends IntegrationTestCase
{
    public function testUnauthenticatedReturns401(): void
    {
        $client = static::createClient();
        $workspace = WorkspaceFactory::createOne();

        $client->request('GET', '/api/workspaces/' . $workspace->getId() . '/projects');

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testNonMemberReturns403(): void
    {
        $client = static::createClient();
        UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request('GET', '/api/workspaces/' . $workspace->getId() . '/projects');

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testOwnerSeesAllProjectsIncludingDrafts(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        $membership = UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Owner]);

        ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $membership, 'name' => 'Active Project', 'status' => ProjectStatus::Active]);
        ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $membership, 'name' => 'Draft Project', 'status' => ProjectStatus::Draft]);
        ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $membership, 'name' => 'Archived Project', 'status' => ProjectStatus::Archived]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request('GET', '/api/workspaces/' . $workspace->getId() . '/projects');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        /** @var list<array<string, string>> $data */
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertCount(3, $data);
    }

    public function testMemberSeesNonDraftAndOwnDrafts(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $otherUser = UserFactory::createOne();
        $workspace = WorkspaceFactory::createOne();
        $membership = UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Member]);
        $otherMembership = UserWorkspaceFactory::createOne(['user' => $otherUser, 'workspace' => $workspace, 'role' => WorkspaceRole::Member]);

        ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $membership, 'name' => 'Own Active', 'status' => ProjectStatus::Active]);
        ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $membership, 'name' => 'Own Draft', 'status' => ProjectStatus::Draft]);
        ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $otherMembership, 'name' => 'Other Draft', 'status' => ProjectStatus::Draft]);
        ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $otherMembership, 'name' => 'Other Archived', 'status' => ProjectStatus::Archived]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request('GET', '/api/workspaces/' . $workspace->getId() . '/projects');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        /** @var list<array<string, string>> $data */
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $names = array_column($data, 'name');
        self::assertCount(3, $data);
        self::assertContains('Own Active', $names);
        self::assertContains('Own Draft', $names);
        self::assertContains('Other Archived', $names);
        self::assertNotContains('Other Draft', $names);
    }

    public function testGuestSeesOnlyAssignedProjects(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        $otherMembership = UserWorkspaceFactory::createOne(['workspace' => $workspace, 'role' => WorkspaceRole::Owner]);
        UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Guest]);

        $assignedProject = ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $otherMembership, 'name' => 'Assigned', 'status' => ProjectStatus::Active]);
        ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $otherMembership, 'name' => 'Not Assigned', 'status' => ProjectStatus::Active]);

        UserProjectFactory::createOne(['project' => $assignedProject, 'user' => $user]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request('GET', '/api/workspaces/' . $workspace->getId() . '/projects');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        /** @var list<array<string, mixed>> $data */
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertCount(1, $data);
        self::assertSame('Assigned', $data[0]['name']);
    }

    public function testGuestWithNoAssignmentsSeesEmptyList(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        $otherMembership = UserWorkspaceFactory::createOne(['workspace' => $workspace, 'role' => WorkspaceRole::Owner]);
        UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Guest]);

        ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $otherMembership, 'status' => ProjectStatus::Active]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request('GET', '/api/workspaces/' . $workspace->getId() . '/projects');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        /** @var list<mixed> $data */
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertCount(0, $data);
    }

    public function testResponseShapeIsCorrect(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        $membership = UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Owner]);

        ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $membership, 'name' => 'Test Project', 'status' => ProjectStatus::Active]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request('GET', '/api/workspaces/' . $workspace->getId() . '/projects');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        /** @var list<array<string, mixed>> $data */
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertCount(1, $data);
        $project = $data[0];
        self::assertArrayHasKey('id', $project);
        self::assertArrayHasKey('name', $project);
        self::assertArrayHasKey('description', $project);
        self::assertArrayHasKey('status', $project);
        self::assertArrayHasKey('createdAt', $project);
        self::assertArrayHasKey('updatedAt', $project);
        self::assertSame('Test Project', $project['name']);
        self::assertSame('active', $project['status']);
    }
}
