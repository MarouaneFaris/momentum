import { Button } from '@/components/ui/button'
import { usePendingInvitationList } from '../hooks/usePendingInvitationList'

type Props = {
    workspaceId: string
}

export function PendingInvitationList({ workspaceId }: Props) {
    const { invitations, isLoading, isPending, handleCancel } =
        usePendingInvitationList(workspaceId)

    if (isLoading) return <p className="text-sm text-muted-foreground">Loading invitations…</p>

    if (!invitations || invitations.length === 0) {
        return <p className="text-sm text-muted-foreground">No pending invitations.</p>
    }

    return (
        <ul className="flex flex-col gap-2">
            {invitations.map((inv) => (
                <li
                    key={inv.id}
                    className="flex items-center justify-between rounded border px-3 py-2 text-sm"
                >
                    <div className="flex flex-col gap-0.5">
                        <span className="font-medium">{inv.invitee.email}</span>
                        <span className="text-xs text-muted-foreground capitalize">
                            {inv.role} · expires {new Date(inv.expiresAt).toLocaleDateString()}
                        </span>
                    </div>
                    <Button
                        variant="ghost"
                        size="sm"
                        disabled={isPending}
                        onClick={() => handleCancel(inv.id)}
                    >
                        Cancel
                    </Button>
                </li>
            ))}
        </ul>
    )
}
