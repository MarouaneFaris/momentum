import { Badge } from '@/components/ui/badge'
import type { WorkspaceRole } from '@/features/membership/types'

type Props = {
    role: WorkspaceRole
}

export function RoleBadge({ role }: Props) {
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
