import { EmptyState } from '@/components/EmptyState'
import { Button } from '@/components/ui/button'
import { MailOpen } from 'lucide-react'
import { useInvitationActions } from '../hooks/useInvitationActions'

export function InvitationsPage() {
    const { invitations, isLoading, isAccepting, isDeclining, handleAccept, handleDecline } =
        useInvitationActions()

    if (isLoading) return <p className="text-muted-foreground text-sm">Loading…</p>

    if (!invitations || invitations.length === 0) {
        return (
            <EmptyState
                icon={MailOpen}
                title="No pending invitations"
                description="You have no workspace invitations at the moment."
            />
        )
    }

    return (
        <ul className="flex flex-col gap-3">
            {invitations.map((inv) => (
                <li
                    key={inv.id}
                    className="flex items-center justify-between rounded border px-4 py-3"
                >
                    <div className="flex flex-col gap-0.5">
                        <span className="font-medium">{inv.workspace.name}</span>
                        <span className="text-muted-foreground text-sm">
                            Invited as <span className="capitalize">{inv.role}</span>
                            {inv.invitedBy ? ` by ${inv.invitedBy.name}` : ''}
                            {' · expires '}
                            {new Date(inv.expiresAt).toLocaleDateString()}
                        </span>
                    </div>
                    <div className="flex gap-2">
                        <Button
                            size="sm"
                            disabled={isAccepting || isDeclining}
                            onClick={() => handleAccept(inv.id)}
                        >
                            Accept
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            disabled={isAccepting || isDeclining}
                            onClick={() => handleDecline(inv.id)}
                        >
                            Decline
                        </Button>
                    </div>
                </li>
            ))}
        </ul>
    )
}
