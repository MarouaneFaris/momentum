import { useState } from 'react'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
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
import { useMemberList } from '../hooks/useMemberList'
import type { Member } from '../types'

type Props = {
    workspaceId: string
    currentUserId: string
    isOwner: boolean
    workspaceName: string
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
            <Badge variant="secondary" className="capitalize">
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

export function MembersTable({ workspaceId, currentUserId, isOwner, workspaceName }: Props) {
    const { members, isLoading, isChanging, isRemoving, handleRoleChange, handleRemove } =
        useMemberList(workspaceId)
    const [removingMember, setRemovingMember] = useState<Member | null>(null)

    if (isLoading) return <p className="text-muted-foreground text-sm">Loading members…</p>

    if (!members || members.length === 0) {
        return <p className="text-muted-foreground text-sm">No members.</p>
    }

    return (
        <>
            <div className="rounded-md border">
                <table className="w-full text-sm">
                    <thead>
                        <tr className="bg-muted/50 border-b">
                            <th className="text-muted-foreground w-[40%] px-4 py-2.5 text-left font-medium">
                                Member
                            </th>
                            <th className="text-muted-foreground px-4 py-2.5 text-left font-medium">
                                Role
                            </th>
                            <th className="text-muted-foreground px-4 py-2.5 text-left font-medium">
                                Joined
                            </th>
                            <th className="text-muted-foreground px-4 py-2.5 text-right font-medium">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        {members.map((member) => {
                            const isSelf = member.id === currentUserId
                            const canManage = isOwner && !isSelf && member.role !== 'owner'

                            return (
                                <tr
                                    key={member.id}
                                    className="hover:bg-muted/30 border-b last:border-0"
                                >
                                    <td className="px-4 py-3">
                                        <div className="flex items-center gap-3">
                                            <MemberAvatar name={member.name} />
                                            <div className="flex flex-col gap-0.5">
                                                <span className="font-medium">{member.name}</span>
                                                <span className="text-muted-foreground text-xs">
                                                    {member.email}
                                                </span>
                                            </div>
                                        </div>
                                    </td>
                                    <td className="px-4 py-3">
                                        {canManage ? (
                                            <Select
                                                value={member.role}
                                                disabled={isChanging}
                                                onValueChange={(role) =>
                                                    handleRoleChange(member.id, role)
                                                }
                                            >
                                                <SelectTrigger
                                                    size="sm"
                                                    className="hover:bg-muted w-28 border-none bg-transparent shadow-none focus:ring-0"
                                                >
                                                    <SelectValue />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="member">Member</SelectItem>
                                                    <SelectItem value="guest">Guest</SelectItem>
                                                </SelectContent>
                                            </Select>
                                        ) : (
                                            <RoleBadge role={member.role} />
                                        )}
                                    </td>
                                    <td className="text-muted-foreground px-4 py-3 text-xs">
                                        {new Date(member.joinedAt).toLocaleDateString('en-US', {
                                            month: 'short',
                                            day: 'numeric',
                                            year: 'numeric',
                                        })}
                                    </td>
                                    <td className="px-4 py-3 text-right">
                                        {isSelf && member.role === 'owner' && (
                                            <span className="text-muted-foreground text-xs italic">
                                                You
                                            </span>
                                        )}
                                        {canManage && (
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                className="text-destructive hover:text-destructive"
                                                onClick={() => setRemovingMember(member)}
                                            >
                                                Remove
                                            </Button>
                                        )}
                                    </td>
                                </tr>
                            )
                        })}
                    </tbody>
                </table>
            </div>

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
                            className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
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
        </>
    )
}
