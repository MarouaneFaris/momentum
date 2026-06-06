<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Enum\ProjectStatus;
use App\Enum\WorkspaceRole;
use App\Factory\ProjectFactory;
use App\Factory\UserFactory;
use App\Factory\UserWorkspaceFactory;
use App\Factory\WorkspaceFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

final class ProjectUpdateTest extends WebTestCase
{
    use Factories;
    use LoginAsTrait;
    use ResetDatabase;

    private const string EMAIL = 'user@example.com';
    private const string PASSWORD = 'SuperSecurePass123!';

    protected function tearDown(): void
    {
        $apiLimiter = static::getContainer()->get('limiter.api');
        assert($apiLimiter instanceof RateLimiterFactory);
        $apiLimiter->create(self::EMAIL)->reset();

        parent::tearDown();
    }

    public function testUnauthenticatedReturns401(): void
    {
        $client = static::createClient();
        $workspace = WorkspaceFactory::createOne();
        $ownerMembership = UserWorkspaceFactory::createOne(['workspace' => $workspace, 'role' => WorkspaceRole::Owner]);
        $project = ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $ownerMembership]);

        $client->request('PATCH', '/api/workspaces/' . $workspace->getId() . '/projects/' . $project->getId(), [], [], ['CONTENT_TYPE' => 'application/json'], '{"name":"Updated"}');

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
        $client->request('PATCH', '/api/workspaces/' . $workspace->getId() . '/projects/' . $project->getId(), [], [], ['CONTENT_TYPE' => 'application/json'], '{"name":"Updated"}');

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testMemberNonOwnerReturns403(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $otherUser = UserFactory::createOne();
        $workspace = WorkspaceFactory::createOne();
        $userMembership = UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Member]);
        $otherMembership = UserWorkspaceFactory::createOne(['user' => $otherUser, 'workspace' => $workspace, 'role' => WorkspaceRole::Member]);
        $project = ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $otherMembership]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request('PATCH', '/api/workspaces/' . $workspace->getId() . '/projects/' . $project->getId(), [], [], ['CONTENT_TYPE' => 'application/json'], '{"name":"Updated"}');

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testOwnerCanEditAnyProject(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $otherUser = UserFactory::createOne();
        $workspace = WorkspaceFactory::createOne();
        UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Owner]);
        $otherMembership = UserWorkspaceFactory::createOne(['user' => $otherUser, 'workspace' => $workspace, 'role' => WorkspaceRole::Member]);
        $project = ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $otherMembership, 'name' => 'Original']);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request(
            'PATCH',
            '/api/workspaces/' . $workspace->getId() . '/projects/' . $project->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['name' => 'Updated', 'status' => 'active'], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        /** @var array<string, mixed> $data */
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertSame('Updated', $data['name']);
        self::assertSame('active', $data['status']);
    }

    public function testMemberCanEditOwnProject(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        $membership = UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Member]);
        $project = ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $membership, 'name' => 'My Project', 'status' => ProjectStatus::Draft]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request(
            'PATCH',
            '/api/workspaces/' . $workspace->getId() . '/projects/' . $project->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['name' => 'Updated Name'], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        /** @var array<string, mixed> $data */
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertSame('Updated Name', $data['name']);
        self::assertSame('draft', $data['status']);
    }

    public function testInvalidStatusReturns422(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        $membership = UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Owner]);
        $project = ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $membership]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request(
            'PATCH',
            '/api/workspaces/' . $workspace->getId() . '/projects/' . $project->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['status' => 'invalid'], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testInvalidTransitionReturns422(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        $membership = UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Owner]);
        $project = ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $membership, 'status' => ProjectStatus::Draft]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request(
            'PATCH',
            '/api/workspaces/' . $workspace->getId() . '/projects/' . $project->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['status' => 'archived'], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        /** @var array<string, mixed> $data */
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertArrayHasKey('message', $data);
    }

    public function testValidTransitionDraftToActiveSucceeds(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        $membership = UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Owner]);
        $project = ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $membership, 'status' => ProjectStatus::Draft]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request(
            'PATCH',
            '/api/workspaces/' . $workspace->getId() . '/projects/' . $project->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['status' => 'active'], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        /** @var array<string, mixed> $data */
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertSame('active', $data['status']);
    }

    public function testValidTransitionActiveToArchivedSucceeds(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        $membership = UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Owner]);
        $project = ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $membership, 'status' => ProjectStatus::Active]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request(
            'PATCH',
            '/api/workspaces/' . $workspace->getId() . '/projects/' . $project->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['status' => 'archived'], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        /** @var array<string, mixed> $data */
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertSame('archived', $data['status']);
    }

    public function testValidTransitionArchivedToActiveSucceeds(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        $membership = UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Owner]);
        $project = ProjectFactory::createOne(['workspace' => $workspace, 'owner' => $membership, 'status' => ProjectStatus::Archived]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request(
            'PATCH',
            '/api/workspaces/' . $workspace->getId() . '/projects/' . $project->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['status' => 'active'], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        /** @var array<string, mixed> $data */
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertSame('active', $data['status']);
    }

    public function testProjectNotFoundReturns404(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Owner]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request('PATCH', '/api/workspaces/' . $workspace->getId() . '/projects/00000000-0000-0000-0000-000000000000', [], [], ['CONTENT_TYPE' => 'application/json'], '{"name":"X"}');

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
