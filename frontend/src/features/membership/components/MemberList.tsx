import { Button } from '@/components/ui/button'
import ApiError from '@/lib/ApiError'
import { useNavigate } from 'react-router'
import { toast } from 'sonner'
import {
    useChangeMemberRole,
    useLeaveWorkspace,
    useRemoveMember,
    useWorkspaceMembers,
} from '../queries'

type Props = {
    workspaceId: string
    currentUserId: string
    isOwner: boolean
}

export function MemberList({ workspaceId, currentUserId, isOwner }: Props) {
    const { data: members, isLoading } = useWorkspaceMembers(workspaceId)
    const { mutate: changeRole, isPending: isChanging } = useChangeMemberRole(workspaceId)
    const { mutate: removeMember, isPending: isRemoving } = useRemoveMember(workspaceId)
    const { mutate: leave, isPending: isLeaving } = useLeaveWorkspace(workspaceId)
    const navigate = useNavigate()

    if (isLoading) return <p className="text-sm text-muted-foreground">Loading members…</p>

    if (!members || members.length === 0) {
        return <p className="text-sm text-muted-foreground">No members.</p>
    }

    return (
        <ul className="flex flex-col gap-2">
            {members.map((member) => {
                const isSelf = member.id === currentUserId
                const canManage = isOwner && !isSelf && member.role !== 'owner'

                return (
                    <li
                        key={member.id}
                        className="flex items-center justify-between rounded border px-3 py-2"
                    >
                        <div className="flex flex-col gap-0.5">
                            <span className="text-sm font-medium">{member.name}</span>
                            <span className="text-xs text-muted-foreground">
                                {member.email} · <span className="capitalize">{member.role}</span>
                            </span>
                        </div>
                        <div className="flex gap-2">
                            {canManage && (
                                <>
                                    <select
                                        className="h-7 rounded border border-input bg-background px-2 text-xs"
                                        value={member.role}
                                        disabled={isChanging}
                                        onChange={(e) =>
                                            changeRole(
                                                { userId: member.id, role: e.target.value },
                                                {
                                                    onError: (err) => {
                                                        if (err instanceof ApiError)
                                                            toast.error(err.message)
                                                    },
                                                },
                                            )
                                        }
                                    >
                                        <option value="member">Member</option>
                                        <option value="guest">Guest</option>
                                    </select>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        disabled={isRemoving}
                                        onClick={() =>
                                            removeMember(member.id, {
                                                onSuccess: () => toast.success('Member removed'),
                                                onError: (err) => {
                                                    if (err instanceof ApiError)
                                                        toast.error(err.message)
                                                },
                                            })
                                        }
                                    >
                                        Remove
                                    </Button>
                                </>
                            )}
                            {!isOwner && isSelf && (
                                <Button
                                    variant="outline"
                                    size="sm"
                                    disabled={isLeaving}
                                    onClick={() =>
                                        leave(undefined, {
                                            onSuccess: () => {
                                                toast.success('Left workspace')
                                                void navigate('/')
                                            },
                                            onError: (err) => {
                                                if (err instanceof ApiError)
                                                    toast.error(err.message)
                                            },
                                        })
                                    }
                                >
                                    Leave
                                </Button>
                            )}
                        </div>
                    </li>
                )
            })}
        </ul>
    )
}
