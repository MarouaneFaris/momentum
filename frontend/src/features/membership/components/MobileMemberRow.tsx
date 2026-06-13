import { MoreHorizontal } from 'lucide-react'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import type { Member } from '../types'

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
            <MemberAvatar name={member.name} />
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
