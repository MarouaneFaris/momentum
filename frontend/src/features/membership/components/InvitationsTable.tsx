import { EmptyState } from '@/components/EmptyState'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { MailOpen } from 'lucide-react'
import { useInvitationsTable } from '../hooks/useInvitationsTable'

type Props = {
    workspaceId: string
}

export function InvitationsTable({ workspaceId }: Props) {
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
                                                onClick={() => handleCancel(inv.id)}
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
                                                onClick={() => handleDelete(inv.id)}
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
        </div>
    )
}
