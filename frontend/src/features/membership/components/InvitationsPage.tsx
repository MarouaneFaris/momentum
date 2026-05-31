import { Button } from '@/components/ui/button'
import ApiError from '@/lib/ApiError'
import { useQueryClient } from '@tanstack/react-query'
import { toast } from 'sonner'
import { useAcceptInvitation, useDeclineInvitation, useMyInvitations } from '../queries'

export function InvitationsPage() {
    const { data: invitations, isLoading } = useMyInvitations()
    const { mutate: accept, isPending: isAccepting } = useAcceptInvitation()
    const { mutate: decline, isPending: isDeclining } = useDeclineInvitation()
    const queryClient = useQueryClient()

    if (isLoading) return <p className="text-sm text-muted-foreground">Loading…</p>

    if (!invitations || invitations.length === 0) {
        return <p className="text-sm text-muted-foreground">No pending invitations.</p>
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
                        <span className="text-sm text-muted-foreground">
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
                            onClick={() =>
                                accept(inv.id, {
                                    onSuccess: () => {
                                        void queryClient.invalidateQueries({
                                            queryKey: ['invitations'],
                                        })
                                        void queryClient.invalidateQueries({
                                            queryKey: ['workspaces'],
                                        })
                                        toast.success('Joined workspace')
                                    },
                                    onError: (err) => {
                                        if (err instanceof ApiError) toast.error(err.message)
                                    },
                                })
                            }
                        >
                            Accept
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            disabled={isAccepting || isDeclining}
                            onClick={() =>
                                decline(inv.id, {
                                    onSuccess: () => {
                                        void queryClient.invalidateQueries({
                                            queryKey: ['invitations'],
                                        })
                                        toast.info('Invitation declined')
                                    },
                                })
                            }
                        >
                            Decline
                        </Button>
                    </div>
                </li>
            ))}
        </ul>
    )
}
