<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Enum\WorkspaceRole;
use App\Factory\UserFactory;
use App\Factory\UserWorkspaceFactory;
use App\Factory\WorkspaceFactory;
use App\Factory\WorkspaceInvitationFactory;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

final class InvitationFlowTest extends WebTestCase
{
    use Factories;
    use ResetDatabase;

    private const string EMAIL = 'owner@example.com';
    private const string PASSWORD = 'SuperSecurePass123!';

    protected function tearDown(): void
    {
        $apiLimiter = static::getContainer()->get('limiter.api');
        assert($apiLimiter instanceof RateLimiterFactory);
        $apiLimiter->create(self::EMAIL)->reset();

        parent::tearDown();
    }

    // — POST /api/workspaces/{workspaceId}/invitations ————————————————————————

    public function testCreateInvitationReturns401WhenUnauthenticated(): void
    {
        $client = static::createClient();
        $workspace = WorkspaceFactory::createOne();

        $client->request(
            'POST',
            '/api/workspaces/' . $workspace->getId() . '/invitations',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'someone@example.com', 'role' => 'member'], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testCreateInvitationReturns403WhenMember(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Member]);
        UserFactory::createOne(['email' => 'invitee@example.com']);

        $this->loginAs($client);
        $client->request(
            'POST',
            '/api/workspaces/' . $workspace->getId() . '/invitations',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'invitee@example.com', 'role' => 'member'], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testCreateInvitationReturns422WhenEmailUnknown(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Owner]);

        $this->loginAs($client);
        $client->request(
            'POST',
            '/api/workspaces/' . $workspace->getId() . '/invitations',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'nobody@example.com', 'role' => 'member'], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testCreateInvitationReturns409WhenDuplicateInvite(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Owner]);
        $invitee = UserFactory::createOne(['email' => 'invitee@example.com']);
        WorkspaceInvitationFactory::createOne([
            'workspace' => $workspace,
            'invitee' => $invitee,
            'expiresAt' => (new \DateTimeImmutable())->modify('+7 days'),
        ]);

        $this->loginAs($client);
        $client->request(
            'POST',
            '/api/workspaces/' . $workspace->getId() . '/invitations',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'invitee@example.com', 'role' => 'member'], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
    }

    public function testCreateInvitationReturns201WithInvitationObject(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Owner]);
        UserFactory::createOne(['email' => 'invitee@example.com']);

        $this->loginAs($client);
        $client->request(
            'POST',
            '/api/workspaces/' . $workspace->getId() . '/invitations',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'invitee@example.com', 'role' => 'member'], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        /** @var array<string, mixed> $data */
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertArrayHasKey('id', $data);
        self::assertSame('member', $data['role']);
        self::assertArrayHasKey('invitee', $data);
        self::assertSame('invitee@example.com', $data['invitee']['email']);
    }

    // — GET /api/workspaces/{workspaceId}/invitations —————————————————————————

    public function testListInvitationsReturns401WhenUnauthenticated(): void
    {
        $client = static::createClient();
        $workspace = WorkspaceFactory::createOne();

        $client->request('GET', '/api/workspaces/' . $workspace->getId() . '/invitations');

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testListInvitationsReturns403WhenMember(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Member]);

        $this->loginAs($client);
        $client->request('GET', '/api/workspaces/' . $workspace->getId() . '/invitations');

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testListInvitationsReturns200WithPendingInvitations(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Owner]);
        WorkspaceInvitationFactory::createOne([
            'workspace' => $workspace,
            'expiresAt' => (new \DateTimeImmutable())->modify('+7 days'),
        ]);
        WorkspaceInvitationFactory::createOne([
            'workspace' => $workspace,
            'expiresAt' => (new \DateTimeImmutable())->modify('-1 day'),
        ]);

        $this->loginAs($client);
        $client->request('GET', '/api/workspaces/' . $workspace->getId() . '/invitations');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        /** @var list<mixed> $data */
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertCount(1, $data);
    }

    // — DELETE /api/workspaces/{workspaceId}/invitations/{invitationId} ————————

    public function testCancelInvitationReturns401WhenUnauthenticated(): void
    {
        $client = static::createClient();
        $workspace = WorkspaceFactory::createOne();
        $invitation = WorkspaceInvitationFactory::createOne(['workspace' => $workspace]);

        $client->request(
            'DELETE',
            '/api/workspaces/' . $workspace->getId() . '/invitations/' . $invitation->getId(),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testCancelInvitationReturns403WhenMember(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Member]);
        $invitation = WorkspaceInvitationFactory::createOne(['workspace' => $workspace]);

        $this->loginAs($client);
        $client->request(
            'DELETE',
            '/api/workspaces/' . $workspace->getId() . '/invitations/' . $invitation->getId(),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testCancelInvitationReturns204(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $workspace = WorkspaceFactory::createOne();
        UserWorkspaceFactory::createOne(['user' => $user, 'workspace' => $workspace, 'role' => WorkspaceRole::Owner]);
        $invitation = WorkspaceInvitationFactory::createOne(['workspace' => $workspace]);

        $this->loginAs($client);
        $client->request(
            'DELETE',
            '/api/workspaces/' . $workspace->getId() . '/invitations/' . $invitation->getId(),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
    }

    // — GET /api/invitations ——————————————————————————————————————————————————

    public function testListMyInvitationsReturns401WhenUnauthenticated(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/invitations');

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testListMyInvitationsReturns200WithOwnInvitations(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        WorkspaceInvitationFactory::createOne([
            'invitee' => $user,
            'expiresAt' => (new \DateTimeImmutable())->modify('+7 days'),
        ]);
        WorkspaceInvitationFactory::createOne([
            'expiresAt' => (new \DateTimeImmutable())->modify('+7 days'),
        ]);

        $this->loginAs($client);
        $client->request('GET', '/api/invitations');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        /** @var list<mixed> $data */
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertCount(1, $data);
        self::assertArrayHasKey('workspace', $data[0]);
        self::assertArrayHasKey('invitedBy', $data[0]);
    }

    // — PUT /api/invitations/{id}/accept ——————————————————————————————————————

    public function testAcceptInvitationReturns401WhenUnauthenticated(): void
    {
        $client = static::createClient();
        $invitation = WorkspaceInvitationFactory::createOne();

        $client->request('PUT', '/api/invitations/' . $invitation->getId() . '/accept');

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testAcceptInvitationReturns403ForWrongInvitee(): void
    {
        $client = static::createClient();
        UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $invitation = WorkspaceInvitationFactory::createOne([
            'expiresAt' => (new \DateTimeImmutable())->modify('+7 days'),
        ]);

        $this->loginAs($client);
        $client->request('PUT', '/api/invitations/' . $invitation->getId() . '/accept');

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testAcceptInvitationReturns410WhenExpired(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $invitation = WorkspaceInvitationFactory::createOne([
            'invitee' => $user,
            'expiresAt' => (new \DateTimeImmutable())->modify('-1 hour'),
        ]);

        $this->loginAs($client);
        $client->request('PUT', '/api/invitations/' . $invitation->getId() . '/accept');

        self::assertResponseStatusCodeSame(Response::HTTP_GONE);
    }

    public function testAcceptInvitationReturns204(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $invitation = WorkspaceInvitationFactory::createOne([
            'invitee' => $user,
            'expiresAt' => (new \DateTimeImmutable())->modify('+7 days'),
        ]);

        $this->loginAs($client);
        $client->request('PUT', '/api/invitations/' . $invitation->getId() . '/accept');

        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
    }

    // — DELETE /api/invitations/{id}/decline ——————————————————————————————————

    public function testDeclineInvitationReturns401WhenUnauthenticated(): void
    {
        $client = static::createClient();
        $invitation = WorkspaceInvitationFactory::createOne();

        $client->request('DELETE', '/api/invitations/' . $invitation->getId() . '/decline');

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testDeclineInvitationReturns403ForWrongInvitee(): void
    {
        $client = static::createClient();
        UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $invitation = WorkspaceInvitationFactory::createOne();

        $this->loginAs($client);
        $client->request('DELETE', '/api/invitations/' . $invitation->getId() . '/decline');

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testDeclineInvitationReturns204(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $invitation = WorkspaceInvitationFactory::createOne([
            'invitee' => $user,
            'expiresAt' => (new \DateTimeImmutable())->modify('+7 days'),
        ]);

        $this->loginAs($client);
        $client->request('DELETE', '/api/invitations/' . $invitation->getId() . '/decline');

        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
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
