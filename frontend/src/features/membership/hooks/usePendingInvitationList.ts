import { useQueryClient } from '@tanstack/react-query'
import { toast } from 'sonner'
import { useCancelInvitation, useWorkspaceInvitations } from '../queries'

export const usePendingInvitationList = (workspaceId: string) => {
    const { data: invitations, isLoading } = useWorkspaceInvitations(workspaceId)
    const { mutate: cancel, isPending } = useCancelInvitation(workspaceId)
    const queryClient = useQueryClient()

    const handleCancel = (invitationId: string) => {
        cancel(invitationId, {
            onSuccess: () => {
                void queryClient.invalidateQueries({
                    queryKey: ['workspaces', workspaceId, 'invitations'],
                })
                toast.success('Invitation cancelled')
            },
        })
    }

    return { invitations, isLoading, isPending, handleCancel }
}
