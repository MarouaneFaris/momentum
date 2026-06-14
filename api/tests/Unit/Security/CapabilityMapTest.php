<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Enum\WorkspaceRole;
use App\Security\Capability;
use App\Security\CapabilityMap;
use PHPUnit\Framework\TestCase;

final class CapabilityMapTest extends TestCase
{
    // Owner — all capabilities except LEAVE_WORKSPACE (owner must delete instead)
    #[\PHPUnit\Framework\Attributes\DataProvider('provideOwnerCapabilities')]
    public function testOwnerHasCapability(Capability $capability): void
    {
        self::assertContains($capability, CapabilityMap::for(WorkspaceRole::Owner));
    }

    public function testOwnerCannotLeaveWorkspace(): void
    {
        self::assertNotContains(Capability::LEAVE_WORKSPACE, CapabilityMap::for(WorkspaceRole::Owner));
    }

    /** @return iterable<string, array{Capability}> */
    public static function provideOwnerCapabilities(): iterable
    {
        foreach (Capability::cases() as $cap) {
            if ($cap === Capability::LEAVE_WORKSPACE) {
                continue;
            }
            yield $cap->name => [$cap];
        }
    }

    // Member
    #[\PHPUnit\Framework\Attributes\DataProvider('provideMemberGranted')]
    public function testMemberHasCapability(Capability $capability): void
    {
        self::assertContains($capability, CapabilityMap::for(WorkspaceRole::Member));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideMemberDenied')]
    public function testMemberLacksCapability(Capability $capability): void
    {
        self::assertNotContains($capability, CapabilityMap::for(WorkspaceRole::Member));
    }

    /** @return iterable<string, array{Capability}> */
    public static function provideMemberGranted(): iterable
    {
        $granted = [
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
        foreach ($granted as $cap) {
            yield $cap->name => [$cap];
        }
    }

    /** @return iterable<string, array{Capability}> */
    public static function provideMemberDenied(): iterable
    {
        $denied = [
            Capability::WORKSPACE_RENAME,
            Capability::WORKSPACE_DELETE,
            Capability::MEMBER_INVITE,
            Capability::MEMBER_CANCEL_INVITATION,
            Capability::MEMBER_VIEW_INVITATIONS,
            Capability::MEMBER_REMOVE,
            Capability::MEMBER_CHANGE_ROLE,
        ];
        foreach ($denied as $cap) {
            yield $cap->name => [$cap];
        }
    }

    // Guest
    #[\PHPUnit\Framework\Attributes\DataProvider('provideGuestGranted')]
    public function testGuestHasCapability(Capability $capability): void
    {
        self::assertContains($capability, CapabilityMap::for(WorkspaceRole::Guest));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideGuestDenied')]
    public function testGuestLacksCapability(Capability $capability): void
    {
        self::assertNotContains($capability, CapabilityMap::for(WorkspaceRole::Guest));
    }

    /** @return iterable<string, array{Capability}> */
    public static function provideGuestGranted(): iterable
    {
        $granted = [
            Capability::WORKSPACE_VIEW,
            Capability::MEMBER_VIEW,
            Capability::LEAVE_WORKSPACE,
            Capability::PROJECT_VIEW,
        ];
        foreach ($granted as $cap) {
            yield $cap->name => [$cap];
        }
    }

    /** @return iterable<string, array{Capability}> */
    public static function provideGuestDenied(): iterable
    {
        $denied = [
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
        foreach ($denied as $cap) {
            yield $cap->name => [$cap];
        }
    }
}
