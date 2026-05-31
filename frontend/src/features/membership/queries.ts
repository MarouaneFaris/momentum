import api from '@/lib/api'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import type { InvitationInviteeView, InvitationOwnerView, InvitationRole, Member } from './types'

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

export const useWorkspaceMembers = (workspaceId: string) =>
    useQuery({
        queryKey: ['workspaces', workspaceId, 'members'],
        queryFn: () => api.get<Member[]>(`/workspaces/${workspaceId}/members`),
    })

export const useChangeMemberRole = (workspaceId: string) => {
    const queryClient = useQueryClient()
    return useMutation({
        mutationFn: ({ userId, role }: { userId: string; role: string }) =>
            api.patch<Member>(`/workspaces/${workspaceId}/members/${userId}`, { role }),
        onSuccess: () => {
            void queryClient.invalidateQueries({
                queryKey: ['workspaces', workspaceId, 'members'],
            })
        },
    })
}

export const useRemoveMember = (workspaceId: string) => {
    const queryClient = useQueryClient()
    return useMutation({
        mutationFn: (userId: string) =>
            api.delete<null>(`/workspaces/${workspaceId}/members/${userId}`),
        onSuccess: () => {
            void queryClient.invalidateQueries({
                queryKey: ['workspaces', workspaceId, 'members'],
            })
        },
    })
}

export const useLeaveWorkspace = (workspaceId: string) => {
    const queryClient = useQueryClient()
    return useMutation({
        mutationFn: () => api.delete<null>(`/workspaces/${workspaceId}/members/me`),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: ['workspaces'] })
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
