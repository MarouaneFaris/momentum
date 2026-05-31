import { Button } from '@/components/ui/button'
import { toast } from 'sonner'
import { useCancelInvitation, useWorkspaceInvitations } from '../queries'

type Props = {
    workspaceId: string
}

export function PendingInvitationList({ workspaceId }: Props) {
    const { data: invitations, isLoading } = useWorkspaceInvitations(workspaceId)
    const { mutate: cancel, isPending } = useCancelInvitation(workspaceId)

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
                        onClick={() =>
                            cancel(inv.id, {
                                onSuccess: () => toast.success('Invitation cancelled'),
                            })
                        }
                    >
                        Cancel
                    </Button>
                </li>
            ))}
        </ul>
    )
}
