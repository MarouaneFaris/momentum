import ApiError from '@/lib/ApiError'
import { useQueryClient } from '@tanstack/react-query'
import { toast } from 'sonner'
import type { InvitationRole } from '../types'
import {
    useCancelInvitation,
    useCreateInvitation,
    useResendInvitation,
    useWorkspaceInvitations,
} from '../queries'

export const useInvitationsTable = (workspaceId: string) => {
    const { data: invitations, isLoading } = useWorkspaceInvitations(workspaceId)
    const { mutate: cancel, isPending: isCancelling } = useCancelInvitation(workspaceId)
    const { mutate: resend, isPending: isResending } = useResendInvitation(workspaceId)
    const { mutate: createInvitation, isPending: isReinviting } = useCreateInvitation(workspaceId)
    const { mutate: deleteInvitation, isPending: isDeleting } = useCancelInvitation(workspaceId)
    const queryClient = useQueryClient()

    const invalidate = () =>
        queryClient.invalidateQueries({
            queryKey: ['workspaces', workspaceId, 'invitations'],
        })

    const handleCancel = (invitationId: string) => {
        cancel(invitationId, {
            onSuccess: () => {
                void invalidate()
                toast.success('Invitation cancelled')
            },
            onError: (err) => {
                if (err instanceof ApiError) toast.error(err.message)
            },
        })
    }

    const handleResend = (invitationId: string) => {
        resend(invitationId, {
            onSuccess: () => {
                void invalidate()
                toast.success('Invitation resent')
            },
            onError: (err) => {
                if (err instanceof ApiError) toast.error(err.message)
            },
        })
    }

    const handleReinvite = (email: string, role: InvitationRole) => {
        createInvitation(
            { email, role },
            {
                onSuccess: () => {
                    void invalidate()
                    toast.success('Invitation sent')
                },
                onError: (err) => {
                    if (err instanceof ApiError) toast.error(err.message)
                },
            },
        )
    }

    const handleDelete = (invitationId: string) => {
        deleteInvitation(invitationId, {
            onSuccess: () => {
                void invalidate()
                toast.success('Invitation deleted')
            },
            onError: (err) => {
                if (err instanceof ApiError) toast.error(err.message)
            },
        })
    }

    return {
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
    }
}
