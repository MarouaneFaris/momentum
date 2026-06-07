import { Button } from '@/components/ui/button'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'
import { useMemberList } from '../hooks/useMemberList'

type Props = {
    workspaceId: string
    currentUserId: string
    isOwner: boolean
}

export function MemberList({ workspaceId, currentUserId, isOwner }: Props) {
    const {
        members,
        isLoading,
        isChanging,
        isRemoving,
        isLeaving,
        handleRoleChange,
        handleRemove,
        handleLeave,
    } = useMemberList(workspaceId)

    if (isLoading) return <p className="text-muted-foreground text-sm">Loading members…</p>

    if (!members || members.length === 0) {
        return <p className="text-muted-foreground text-sm">No members.</p>
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
                            <span className="text-muted-foreground text-xs">
                                {member.email} · <span className="capitalize">{member.role}</span>
                            </span>
                        </div>
                        <div className="flex gap-2">
                            {canManage && (
                                <>
                                    <Select
                                        value={member.role}
                                        disabled={isChanging}
                                        onValueChange={(role) => handleRoleChange(member.id, role)}
                                    >
                                        <SelectTrigger size="sm" className="w-28">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="member">Member</SelectItem>
                                            <SelectItem value="guest">Guest</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        disabled={isRemoving}
                                        onClick={() => handleRemove(member.id)}
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
                                    onClick={handleLeave}
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
