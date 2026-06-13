import { useState } from 'react'
import { EmptyState } from '@/components/EmptyState'
import { ConfirmDialog } from '@/components/ConfirmDialog'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { MailOpen } from 'lucide-react'
import { useInvitationsTable } from '../hooks/useInvitationsTable'
import type { InvitationOwnerView } from '../types'

type ConfirmAction = { type: 'cancel' | 'delete'; inv: InvitationOwnerView }

type Props = {
    workspaceId: string
    workspaceName: string
}

export function InvitationsTable({ workspaceId, workspaceName }: Props) {
    const {
        invitations,
        isLoading,
        isCancelling,
        isResending,
        isReinviting,
        isDeleting,
        handleCancel,
        handleResend,
        handleReinvite,
        handleDelete,
    } = useInvitationsTable(workspaceId)
    const [confirmAction, setConfirmAction] = useState<ConfirmAction | null>(null)

    if (isLoading) return <p className="text-muted-foreground text-sm">Loading invitations…</p>

    if (!invitations || invitations.length === 0) {
        return (
            <EmptyState
                icon={MailOpen}
                title="No invitations yet"
                description="Invite members from the Members tab."
            />
        )
    }

    return (
        <div className="rounded-md border">
            <table className="w-full text-sm">
                <thead>
                    <tr className="bg-muted/50 border-b">
                        <th className="text-muted-foreground w-[30%] px-4 py-2.5 text-left font-medium">
                            Email
                        </th>
                        <th className="text-muted-foreground px-4 py-2.5 text-left font-medium">
                            Role
                        </th>
                        <th className="text-muted-foreground px-4 py-2.5 text-left font-medium">
                            Status
                        </th>
                        <th className="text-muted-foreground px-4 py-2.5 text-left font-medium">
                            Sent
                        </th>
                        <th className="text-muted-foreground px-4 py-2.5 text-left font-medium">
                            Expires
                        </th>
                        <th className="text-muted-foreground px-4 py-2.5 text-right font-medium">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody>
                    {invitations.map((inv) => (
                        <tr key={inv.id} className="hover:bg-muted/30 border-b last:border-0">
                            <td className="px-4 py-3 text-sm">{inv.invitee.email}</td>
                            <td className="px-4 py-3">
                                <Badge variant="outline" className="capitalize">
                                    {inv.role}
                                </Badge>
                            </td>
                            <td className="px-4 py-3">
                                {inv.status === 'pending' ? (
                                    <Badge variant="secondary">Pending</Badge>
                                ) : (
                                    <Badge variant="outline" className="text-muted-foreground">
                                        Expired
                                    </Badge>
                                )}
                            </td>
                            <td className="text-muted-foreground px-4 py-3 text-xs">
                                {new Date(inv.createdAt).toLocaleDateString('en-US', {
                                    month: 'short',
                                    day: 'numeric',
                                    year: 'numeric',
                                })}
                            </td>
                            <td className="text-muted-foreground px-4 py-3 text-xs">
                                {new Date(inv.expiresAt).toLocaleDateString('en-US', {
                                    month: 'short',
                                    day: 'numeric',
                                    year: 'numeric',
                                })}
                            </td>
                            <td className="px-4 py-3 text-right">
                                <div className="flex justify-end gap-1">
                                    {inv.status === 'pending' ? (
                                        <>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                disabled={isResending}
                                                onClick={() => handleResend(inv.id)}
                                            >
                                                Resend
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                className="text-destructive hover:text-destructive"
                                                disabled={isCancelling}
                                                onClick={() =>
                                                    setConfirmAction({ type: 'cancel', inv })
                                                }
                                            >
                                                Cancel
                                            </Button>
                                        </>
                                    ) : (
                                        <>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                disabled={isReinviting}
                                                onClick={() =>
                                                    handleReinvite(inv.invitee.email, inv.role)
                                                }
                                            >
                                                Reinvite
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                className="text-destructive hover:text-destructive"
                                                disabled={isDeleting}
                                                onClick={() =>
                                                    setConfirmAction({ type: 'delete', inv })
                                                }
                                            >
                                                Delete
                                            </Button>
                                        </>
                                    )}
                                </div>
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>

            <ConfirmDialog
                open={confirmAction !== null}
                onOpenChange={(open) => {
                    if (!open) setConfirmAction(null)
                }}
                title={
                    confirmAction?.type === 'cancel' ? 'Cancel invitation?' : 'Delete invitation?'
                }
                description={
                    confirmAction?.type === 'cancel'
                        ? `${confirmAction.inv.invitee.email} will no longer be able to join ${workspaceName}.`
                        : 'This cannot be undone.'
                }
                confirmLabel={confirmAction?.type === 'cancel' ? 'Cancel invitation' : 'Delete'}
                onConfirm={() => {
                    if (!confirmAction) return
                    if (confirmAction.type === 'cancel') {
                        handleCancel(confirmAction.inv.id)
                    } else {
                        handleDelete(confirmAction.inv.id)
                    }
                    setConfirmAction(null)
                }}
                isPending={confirmAction?.type === 'cancel' ? isCancelling : isDeleting}
            />
        </div>
    )
}
