import { MoreHorizontal } from 'lucide-react'
import { Button } from '@/components/ui/button'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { RoleBadge } from '@/components/RoleBadge'
import { UserAvatar } from '@/components/UserAvatar'
import type { Member } from '../types'

export function MobileMemberRow({
    member,
    canManage,
    onChangeRole,
    onRemove,
}: {
    member: Member
    canManage: boolean
    onChangeRole: (member: Member) => void
    onRemove: (member: Member) => void
}) {
    return (
        <div className="border-border flex items-center gap-3 border-b px-3 py-2.5 last:border-0">
            <UserAvatar name={member.name} size="md" />
            <div className="min-w-0 flex-1">
                <p className="text-foreground truncate text-xs font-medium">{member.name}</p>
                <p className="text-muted-foreground truncate text-xs">{member.email}</p>
            </div>
            <RoleBadge role={member.role} />
            {canManage ? (
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
                        <DropdownMenuItem onClick={() => onChangeRole(member)}>
                            Change role
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            className="text-destructive"
                            onClick={() => onRemove(member)}
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
}
