import { useState } from 'react'
import { useNavigate } from 'react-router'
import { Check } from 'lucide-react'
import { Controller } from 'react-hook-form'
import { MobileLayout } from '@/components/MobileLayout'
import { cn } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog'
import { BottomSheet } from '@/components/BottomSheet'
import { ConfirmDialog } from '@/components/ConfirmDialog'
import { useMemberList } from '../hooks/useMemberList'
import { useInviteForm } from '../hooks/useInviteForm'
import { useInvitationsTable } from '../hooks/useInvitationsTable'
import type { Member } from '../types'
import { MobileMemberRow } from './MobileMemberRow'
import { MobileExpiredInvitationRow } from './MobileExpiredInvitationRow'
import { MobilePendingInvitationRow } from './MobilePendingInvitationRow'

type Props = {
    workspaceId: string
    workspaceName: string
    isOwner: boolean
    currentUserId: string
}

export function MobileMembersView({ workspaceId, workspaceName, isOwner, currentUserId }: Props) {
    const navigate = useNavigate()
    const {
        members,
        isRemoving,
        isChanging,
        isLeaving,
        handleRemove,
        handleRoleChange,
        handleLeave,
    } = useMemberList(workspaceId)
    const {
        invitations,
        isCancelling,
        isResending,
        isReinviting,
        isDeleting,
        handleCancel,
        handleResend,
        handleReinvite,
        handleDelete,
    } = useInvitationsTable(workspaceId)
    const invite = useInviteForm(workspaceId)

    const [inviteOpen, setInviteOpen] = useState(false)
    const [removingMember, setRemovingMember] = useState<Member | null>(null)
    const [changingRoleMember, setChangingRoleMember] = useState<Member | null>(null)
    const [leaveConfirmOpen, setLeaveConfirmOpen] = useState(false)

    const pendingInvitations = invitations?.filter((inv) => inv.status === 'pending') ?? []
    const expiredInvitations = invitations?.filter((inv) => inv.status === 'expired') ?? []

    return (
        <MobileLayout
            title="Members"
            onBack={() => void navigate(-1)}
            action={
                isOwner ? (
                    <Button
                        variant="ghost"
                        size="sm"
                        className="text-primary h-8 px-2 text-xs font-medium"
                        onClick={() => setInviteOpen(true)}
                    >
                        Invite
                    </Button>
                ) : undefined
            }
        >
            <div className="flex flex-col gap-4 pt-4">
                {/* Members section */}
                <div className="flex flex-col gap-2 px-4">
                    <p className="text-muted-foreground text-xs font-medium">
                        {workspaceName} — {members?.length ?? 0} member
                        {(members?.length ?? 0) !== 1 ? 's' : ''}
                    </p>
                    <div className="rounded-md border">
                        {members?.map((member) => (
                            <MobileMemberRow
                                key={member.id}
                                member={member}
                                canManage={
                                    isOwner &&
                                    member.id !== currentUserId &&
                                    member.role !== 'owner'
                                }
                                onChangeRole={setChangingRoleMember}
                                onRemove={setRemovingMember}
                                onLeave={
                                    member.id === currentUserId && member.role !== 'owner'
                                        ? () => setLeaveConfirmOpen(true)
                                        : undefined
                                }
                            />
                        ))}
                    </div>
                </div>

                {/* Pending invitations — owner only, hidden when empty */}
                {isOwner && pendingInvitations.length > 0 && (
                    <div className="flex flex-col gap-2 px-4">
                        <p className="text-muted-foreground text-xs font-medium">
                            Pending invitations
                        </p>
                        <div className="rounded-md border">
                            {pendingInvitations.map((inv) => (
                                <MobilePendingInvitationRow
                                    key={inv.id}
                                    inv={inv}
                                    isResending={isResending}
                                    isCancelling={isCancelling}
                                    onResend={handleResend}
                                    onCancel={handleCancel}
                                />
                            ))}
                        </div>
                    </div>
                )}

                {/* Expired invitations — owner only, hidden when empty */}
                {isOwner && expiredInvitations.length > 0 && (
                    <div className="flex flex-col gap-2 px-4">
                        <p className="text-muted-foreground text-xs font-medium">
                            Expired invitations
                        </p>
                        <div className="rounded-md border">
                            {expiredInvitations.map((inv) => (
                                <MobileExpiredInvitationRow
                                    key={inv.id}
                                    inv={inv}
                                    isReinviting={isReinviting}
                                    isDeleting={isDeleting}
                                    onReinvite={handleReinvite}
                                    onDelete={handleDelete}
                                />
                            ))}
                        </div>
                    </div>
                )}

                {/* Remove member dialog */}
                <AlertDialog
                    open={removingMember !== null}
                    onOpenChange={(open) => {
                        if (!open) setRemovingMember(null)
                    }}
                >
                    <AlertDialogContent>
                        <AlertDialogHeader>
                            <AlertDialogTitle>Remove {removingMember?.name}?</AlertDialogTitle>
                            <AlertDialogDescription>
                                {removingMember?.name} will lose access to {workspaceName} and all
                                its projects. Their assigned tasks will be unassigned.
                            </AlertDialogDescription>
                        </AlertDialogHeader>
                        <AlertDialogFooter>
                            <AlertDialogCancel>Cancel</AlertDialogCancel>
                            <AlertDialogAction
                                className="bg-destructive/10 text-destructive hover:bg-destructive/20"
                                disabled={isRemoving}
                                onClick={() => {
                                    if (removingMember) {
                                        handleRemove(removingMember.id)
                                        setRemovingMember(null)
                                    }
                                }}
                            >
                                Remove member
                            </AlertDialogAction>
                        </AlertDialogFooter>
                    </AlertDialogContent>
                </AlertDialog>

                {/* Leave workspace dialog */}
                <ConfirmDialog
                    open={leaveConfirmOpen}
                    onOpenChange={setLeaveConfirmOpen}
                    title="Leave workspace?"
                    description={`You will lose access to ${workspaceName} and all its projects.`}
                    confirmLabel="Leave workspace"
                    isPending={isLeaving}
                    onConfirm={handleLeave}
                />

                {/* Change role bottom sheet */}
                <BottomSheet
                    open={changingRoleMember !== null}
                    onOpenChange={(open) => {
                        if (!open) setChangingRoleMember(null)
                    }}
                    title="Change role"
                >
                    <div className="flex flex-col gap-2 px-4 pt-3 pb-6">
                        {(['member', 'guest'] as const).map((role) => (
                            <button
                                key={role}
                                type="button"
                                disabled={isChanging}
                                className={cn(
                                    'flex items-center justify-between rounded-md border px-3 py-2.5 text-sm transition-colors',
                                    changingRoleMember?.role === role
                                        ? 'border-primary bg-primary/10 text-primary'
                                        : 'border-border text-foreground hover:bg-muted',
                                )}
                                onClick={() => {
                                    if (changingRoleMember) {
                                        handleRoleChange(changingRoleMember.id, role)
                                        setChangingRoleMember(null)
                                    }
                                }}
                            >
                                <span className="font-medium capitalize">{role}</span>
                                {changingRoleMember?.role === role && <Check size={14} />}
                            </button>
                        ))}
                    </div>
                </BottomSheet>

                {/* Invite bottom sheet */}
                <BottomSheet
                    open={inviteOpen}
                    onOpenChange={(v) => {
                        setInviteOpen(v)
                        if (!v) invite.form.reset()
                    }}
                    title="Invite member"
                >
                    <form
                        id="mobile-invite-form"
                        onSubmit={(e) => {
                            void invite.form.handleSubmit((values) => {
                                invite.onSubmit(values)
                                setInviteOpen(false)
                            })(e)
                        }}
                        className="flex flex-col gap-4 px-4 pt-4 pb-6"
                    >
                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="m-invite-email">Email address</Label>
                            <Input
                                id="m-invite-email"
                                type="email"
                                placeholder="colleague@company.com"
                                autoFocus
                                {...invite.form.register('email')}
                            />
                            {invite.form.formState.errors.email && (
                                <p className="text-destructive text-sm">
                                    {invite.form.formState.errors.email.message}
                                </p>
                            )}
                        </div>
                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="m-invite-role">Role</Label>
                            <Controller
                                control={invite.form.control}
                                name="role"
                                render={({ field }) => (
                                    <Select value={field.value} onValueChange={field.onChange}>
                                        <SelectTrigger id="m-invite-role" size="sm">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="member">Member</SelectItem>
                                            <SelectItem value="guest">Guest</SelectItem>
                                        </SelectContent>
                                    </Select>
                                )}
                            />
                        </div>
                        <div className="flex justify-end gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => {
                                    setInviteOpen(false)
                                    invite.form.reset()
                                }}
                            >
                                Cancel
                            </Button>
                            <Button
                                type="submit"
                                form="mobile-invite-form"
                                disabled={invite.isPending}
                            >
                                Send
                            </Button>
                        </div>
                    </form>
                </BottomSheet>
            </div>
        </MobileLayout>
    )
}
