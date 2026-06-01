import ApiError from '@/lib/ApiError'
import { useQueryClient } from '@tanstack/react-query'
import { toast } from 'sonner'
import { useAcceptInvitation, useDeclineInvitation, useMyInvitations } from '../queries'

export const useInvitationActions = () => {
    const { data: invitations, isLoading } = useMyInvitations()
    const { mutate: accept, isPending: isAccepting } = useAcceptInvitation()
    const { mutate: decline, isPending: isDeclining } = useDeclineInvitation()
    const queryClient = useQueryClient()

    const handleAccept = (invitationId: string) => {
        accept(invitationId, {
            onSuccess: () => {
                void queryClient.invalidateQueries({ queryKey: ['invitations'] })
                void queryClient.invalidateQueries({ queryKey: ['workspaces'] })
                toast.success('Joined workspace')
            },
            onError: (err) => {
                if (err instanceof ApiError) toast.error(err.message)
            },
        })
    }

    const handleDecline = (invitationId: string) => {
        decline(invitationId, {
            onSuccess: () => {
                void queryClient.invalidateQueries({ queryKey: ['invitations'] })
                toast.info('Invitation declined')
            },
        })
    }

    return { invitations, isLoading, isAccepting, isDeclining, handleAccept, handleDecline }
}
