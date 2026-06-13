import { MoreHorizontal } from 'lucide-react'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { UserAvatar } from '@/components/UserAvatar'
import type { InvitationOwnerView } from '../types'

function daysUntilExpiry(expiresAt: string): number {
    return Math.max(0, Math.ceil((new Date(expiresAt).getTime() - Date.now()) / 86_400_000))
}

export function MobilePendingInvitationRow({
    inv,
    isResending,
    isCancelling,
    onResend,
    onCancel,
}: {
    inv: InvitationOwnerView
    isResending: boolean
    isCancelling: boolean
    onResend: (id: string) => void
    onCancel: (id: string) => void
}) {
    return (
        <div className="border-border flex items-center gap-3 border-b px-3 py-2.5 last:border-0">
            <UserAvatar name="" size="md" />
            <div className="min-w-0 flex-1">
                <p className="text-muted-foreground truncate text-xs">{inv.invitee.email}</p>
                <p className="text-muted-foreground truncate text-xs">
                    Expires in {daysUntilExpiry(inv.expiresAt)} days
                </p>
            </div>
            <Badge variant="secondary" className="text-xs">
                Pending
            </Badge>
            <DropdownMenu>
                <DropdownMenuTrigger asChild>
                    <Button
                        variant="ghost"
                        size="icon"
                        className="h-7 w-7 shrink-0"
                        aria-label="Invitation actions"
                    >
                        <MoreHorizontal size={15} />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                    <DropdownMenuItem disabled={isResending} onClick={() => onResend(inv.id)}>
                        Resend
                    </DropdownMenuItem>
                    <DropdownMenuItem
                        className="text-destructive"
                        disabled={isCancelling}
                        onClick={() => onCancel(inv.id)}
                    >
                        Cancel
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    )
}
