import { useState } from 'react'
import { MoreHorizontal } from 'lucide-react'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { ConfirmDialog } from '@/components/ConfirmDialog'
import { UserAvatar } from '@/components/UserAvatar'
import type { InvitationOwnerView, InvitationRole } from '../types'

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
    const [confirmOpen, setConfirmOpen] = useState(false)

    return (
        <>
            <div className="border-border flex items-center gap-3 border-b px-3 py-2.5 last:border-0">
                <UserAvatar name="" size="md" />
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
                            onClick={() => setConfirmOpen(true)}
                        >
                            Delete
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>

            <ConfirmDialog
                open={confirmOpen}
                onOpenChange={setConfirmOpen}
                title="Delete invitation?"
                description="This cannot be undone."
                confirmLabel="Delete"
                onConfirm={() => {
                    onDelete(inv.id)
                    setConfirmOpen(false)
                }}
                isPending={isDeleting}
            />
        </>
    )
}
