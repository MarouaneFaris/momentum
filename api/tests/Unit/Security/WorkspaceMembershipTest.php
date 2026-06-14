<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Enum\WorkspaceRole;
use App\Security\Capability;
use App\Security\WorkspaceMembership;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class WorkspaceMembershipTest extends TestCase
{
    private static function membership(WorkspaceRole $role): WorkspaceMembership
    {
        return new WorkspaceMembership(
            userId: Uuid::v7(),
            workspaceId: Uuid::v7(),
            role: $role,
        );
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideRoleCapabilityCombinations')]
    public function testCan(WorkspaceRole $role, Capability $capability, bool $expected): void
    {
        self::assertSame($expected, self::membership($role)->can($capability));
    }

    /** @return iterable<string, array{WorkspaceRole, Capability, bool}> */
    public static function provideRoleCapabilityCombinations(): iterable
    {
        // Owner gets everything except LEAVE_WORKSPACE (owner must delete instead)
        foreach (Capability::cases() as $cap) {
            $expected = $cap !== Capability::LEAVE_WORKSPACE;
            yield "owner/{$cap->name}" => [WorkspaceRole::Owner, $cap, $expected];
        }

        // Member grants
        $memberGranted = [
            Capability::WORKSPACE_VIEW,
            Capability::MEMBER_VIEW,
            Capability::LEAVE_WORKSPACE,
            Capability::PROJECT_VIEW,
            Capability::PROJECT_CREATE,
            Capability::PROJECT_EDIT,
            Capability::PROJECT_DELETE,
            Capability::PROJECT_MANAGE_MEMBERS,
            Capability::TASK_VIEW,
            Capability::TASK_CREATE,
            Capability::TASK_EDIT,
            Capability::TASK_STATUS_CHANGE,
            Capability::TASK_DELETE,
        ];
        foreach ($memberGranted as $cap) {
            yield "member/{$cap->name}/granted" => [WorkspaceRole::Member, $cap, true];
        }

        // Member denials
        $memberDenied = [
            Capability::WORKSPACE_RENAME,
            Capability::WORKSPACE_DELETE,
            Capability::MEMBER_INVITE,
            Capability::MEMBER_CANCEL_INVITATION,
            Capability::MEMBER_VIEW_INVITATIONS,
            Capability::MEMBER_REMOVE,
            Capability::MEMBER_CHANGE_ROLE,
        ];
        foreach ($memberDenied as $cap) {
            yield "member/{$cap->name}/denied" => [WorkspaceRole::Member, $cap, false];
        }

        // Guest grants
        $guestGranted = [
            Capability::WORKSPACE_VIEW,
            Capability::MEMBER_VIEW,
            Capability::LEAVE_WORKSPACE,
            Capability::PROJECT_VIEW,
        ];
        foreach ($guestGranted as $cap) {
            yield "guest/{$cap->name}/granted" => [WorkspaceRole::Guest, $cap, true];
        }

        // Guest denials (all others)
        $guestDenied = [
            Capability::WORKSPACE_RENAME,
            Capability::WORKSPACE_DELETE,
            Capability::MEMBER_INVITE,
            Capability::MEMBER_CANCEL_INVITATION,
            Capability::MEMBER_VIEW_INVITATIONS,
            Capability::MEMBER_REMOVE,
            Capability::MEMBER_CHANGE_ROLE,
            Capability::PROJECT_CREATE,
            Capability::PROJECT_EDIT,
            Capability::PROJECT_DELETE,
            Capability::PROJECT_MANAGE_MEMBERS,
            Capability::TASK_VIEW,
            Capability::TASK_CREATE,
            Capability::TASK_EDIT,
            Capability::TASK_STATUS_CHANGE,
            Capability::TASK_DELETE,
        ];
        foreach ($guestDenied as $cap) {
            yield "guest/{$cap->name}/denied" => [WorkspaceRole::Guest, $cap, false];
        }
    }
}
