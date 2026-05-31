import api from '@/lib/api'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import type { InvitationInviteeView, InvitationOwnerView, InvitationRole } from './types'

export const useWorkspaceInvitations = (workspaceId: string) =>
    useQuery({
        queryKey: ['workspaces', workspaceId, 'invitations'],
        queryFn: () => api.get<InvitationOwnerView[]>(`/workspaces/${workspaceId}/invitations`),
    })

export const useCreateInvitation = (workspaceId: string) => {
    const queryClient = useQueryClient()
    return useMutation({
        mutationFn: (data: { email: string; role: InvitationRole }) =>
            api.post<InvitationOwnerView>(`/workspaces/${workspaceId}/invitations`, data),
        onSuccess: () => {
            void queryClient.invalidateQueries({
                queryKey: ['workspaces', workspaceId, 'invitations'],
            })
        },
    })
}

export const useCancelInvitation = (workspaceId: string) => {
    const queryClient = useQueryClient()
    return useMutation({
        mutationFn: (invitationId: string) =>
            api.delete<null>(`/workspaces/${workspaceId}/invitations/${invitationId}`),
        onSuccess: () => {
            void queryClient.invalidateQueries({
                queryKey: ['workspaces', workspaceId, 'invitations'],
            })
        },
    })
}

export const useMyInvitations = () =>
    useQuery({
        queryKey: ['invitations'],
        queryFn: () => api.get<InvitationInviteeView[]>('/invitations'),
    })

export const useAcceptInvitation = () => {
    const queryClient = useQueryClient()
    return useMutation({
        mutationFn: (invitationId: string) => api.post<null>(`/invitations/${invitationId}/accept`),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: ['invitations'] })
            void queryClient.invalidateQueries({ queryKey: ['workspaces'] })
        },
    })
}

export const useDeclineInvitation = () => {
    const queryClient = useQueryClient()
    return useMutation({
        mutationFn: (invitationId: string) =>
            api.post<null>(`/invitations/${invitationId}/decline`),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: ['invitations'] })
        },
    })
}
