import { useState } from 'react'
import { useNavigate } from 'react-router'
import { ArrowLeft, MoreHorizontal } from 'lucide-react'
import { Controller } from 'react-hook-form'
import ApiError from '@/lib/ApiError'
import { useQueryClient } from '@tanstack/react-query'
import { toast } from 'sonner'
import { Badge } from '@/components/ui/badge'
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
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
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
import { useMemberList } from '../hooks/useMemberList'
import { useInviteForm } from '../hooks/useInviteForm'
import { useCancelInvitation, useWorkspaceInvitations } from '../queries'
import type { Member } from '../types'

type Props = {
    workspaceId: string
    workspaceName: string
    isOwner: boolean
    currentUserId: string
}

function MemberAvatar({ name }: { name: string }) {
    const initials = name
        .split(' ')
        .slice(0, 2)
        .map((w) => w[0]?.toUpperCase() ?? '')
        .join('')
    return (
        <div className="bg-primary/10 text-primary flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-medium">
            {initials}
        </div>
    )
}

function RoleBadge({ role }: { role: Member['role'] }) {
    if (role === 'owner')
        return (
            <Badge
                variant="outline"
                className="border-primary/25 bg-primary/10 text-primary capitalize"
            >
                Owner
            </Badge>
        )
    if (role === 'member')
        return (
            <Badge variant="outline" className="capitalize">
                Member
            </Badge>
        )
    return (
        <Badge variant="outline" className="text-muted-foreground capitalize">
            Guest
        </Badge>
    )
}

function daysUntilExpiry(expiresAt: string): number {
    return Math.max(0, Math.ceil((new Date(expiresAt).getTime() - Date.now()) / 86_400_000))
}

export function MobileMembersView({ workspaceId, workspaceName, isOwner, currentUserId }: Props) {
    const navigate = useNavigate()
    const queryClient = useQueryClient()
    const { members, isRemoving, handleRemove } = useMemberList(workspaceId)
    const { data: invitations } = useWorkspaceInvitations(workspaceId, isOwner)
    const { mutate: cancelInvitation, isPending: isCancelling } = useCancelInvitation(workspaceId)
    const invite = useInviteForm(workspaceId)

    const [inviteOpen, setInviteOpen] = useState(false)
    const [removingMember, setRemovingMember] = useState<Member | null>(null)

    const pendingInvitations = invitations?.filter((inv) => inv.status === 'pending') ?? []

    const handleCancelInvitation = (invitationId: string) => {
        cancelInvitation(invitationId, {
            onSuccess: () => {
                void queryClient.invalidateQueries({
                    queryKey: ['workspaces', workspaceId, 'invitations'],
                })
                toast.success('Invitation cancelled')
            },
            onError: (err) => {
                if (err instanceof ApiError) toast.error(err.message)
            },
        })
    }

    return (
        <div className="flex flex-col gap-4">
            {/* Topbar */}
            <div className="bg-background border-border sticky top-0 z-10 flex items-center border-b px-4 py-3">
                <Button
                    variant="ghost"
                    size="icon"
                    className="-ml-1 h-8 w-8 shrink-0"
                    onClick={() => {
                        void navigate(-1)
                    }}
                    aria-label="Back"
                >
                    <ArrowLeft size={18} />
                </Button>
                <span className="text-foreground flex-1 text-center text-sm font-semibold">
                    Members
                </span>
                {isOwner ? (
                    <Button
                        variant="ghost"
                        size="sm"
                        className="text-primary h-8 px-2 text-xs font-medium"
                        onClick={() => setInviteOpen(true)}
                    >
                        Invite
                    </Button>
                ) : (
                    <span className="w-8" aria-hidden />
                )}
            </div>

            {/* Members section */}
            <div className="flex flex-col gap-2 px-4">
                <p className="text-muted-foreground text-xs font-medium">
                    {workspaceName} — {members?.length ?? 0} member
                    {(members?.length ?? 0) !== 1 ? 's' : ''}
                </p>
                <div className="rounded-md border">
                    {members?.map((member) => {
                        const canRemove =
                            isOwner && member.id !== currentUserId && member.role !== 'owner'
                        return (
                            <div
                                key={member.id}
                                className="border-border flex items-center gap-3 border-b px-3 py-2.5 last:border-0"
                            >
                                <MemberAvatar name={member.name} />
                                <div className="min-w-0 flex-1">
                                    <p className="text-foreground truncate text-xs font-medium">
                                        {member.name}
                                    </p>
                                    <p className="text-muted-foreground truncate text-xs">
                                        {member.email}
                                    </p>
                                </div>
                                <RoleBadge role={member.role} />
                                {canRemove ? (
                                    <DropdownMenu>
                                        <DropdownMenuTrigger asChild>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                className="h-7 w-7 shrink-0"
                                                aria-label="Member actions"
                                            >
                                                <MoreHorizontal size={15} />
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end">
                                            <DropdownMenuItem
                                                className="text-destructive"
                                                onClick={() => setRemovingMember(member)}
                                            >
                                                Remove
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                ) : (
                                    <span className="w-7 shrink-0" aria-hidden />
                                )}
                            </div>
                        )
                    })}
                </div>
            </div>

            {/* Pending invitations — owner only, hidden when empty */}
            {isOwner && pendingInvitations.length > 0 && (
                <div className="flex flex-col gap-2 px-4">
                    <p className="text-muted-foreground text-xs font-medium">Pending invitations</p>
                    <div className="rounded-md border">
                        {pendingInvitations.map((inv) => (
                            <div
                                key={inv.id}
                                className="border-border flex items-center gap-3 border-b px-3 py-2.5 last:border-0"
                            >
                                <div className="bg-muted border-border text-muted-foreground flex h-8 w-8 shrink-0 items-center justify-center rounded-full border text-xs font-medium">
                                    ?
                                </div>
                                <div className="min-w-0 flex-1">
                                    <p className="text-muted-foreground truncate text-xs">
                                        {inv.invitee.email}
                                    </p>
                                    <p className="text-muted-foreground truncate text-xs">
                                        Expires in {daysUntilExpiry(inv.expiresAt)} days
                                    </p>
                                </div>
                                <Badge variant="secondary" className="text-xs">
                                    Pending
                                </Badge>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    className="text-destructive hover:text-destructive h-7 shrink-0 px-2 text-xs"
                                    disabled={isCancelling}
                                    onClick={() => handleCancelInvitation(inv.id)}
                                >
                                    Cancel
                                </Button>
                            </div>
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
                            {removingMember?.name} will lose access to {workspaceName} and all its
                            projects. Their assigned tasks will be unassigned.
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
                        <Button type="submit" form="mobile-invite-form" disabled={invite.isPending}>
                            Send
                        </Button>
                    </div>
                </form>
            </BottomSheet>
        </div>
    )
}
