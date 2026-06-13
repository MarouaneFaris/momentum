import { MoreHorizontal } from 'lucide-react'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import type { InvitationOwnerView, InvitationRole } from '../types'

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
            <div className="bg-muted border-border text-muted-foreground flex h-8 w-8 shrink-0 items-center justify-center rounded-full border text-xs font-medium">
                ?
            </div>
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

export function MobileExpiredInvitationRow({
    inv,
    isReinviting,
    isDeleting,
    onReinvite,
    onDelete,
}: {
    inv: InvitationOwnerView
    isReinviting: boolean
    isDeleting: boolean
    onReinvite: (email: string, role: InvitationRole) => void
    onDelete: (id: string) => void
}) {
    return (
        <div className="border-border flex items-center gap-3 border-b px-3 py-2.5 last:border-0">
            <div className="bg-muted border-border text-muted-foreground flex h-8 w-8 shrink-0 items-center justify-center rounded-full border text-xs font-medium">
                ?
            </div>
            <div className="min-w-0 flex-1">
                <p className="text-muted-foreground truncate text-xs">{inv.invitee.email}</p>
            </div>
            <Badge variant="outline" className="text-muted-foreground text-xs">
                Expired
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
                    <DropdownMenuItem
                        disabled={isReinviting}
                        onClick={() => onReinvite(inv.invitee.email, inv.role)}
                    >
                        Reinvite
                    </DropdownMenuItem>
                    <DropdownMenuItem
                        className="text-destructive"
                        disabled={isDeleting}
                        onClick={() => onDelete(inv.id)}
                    >
                        Delete
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    )
}
