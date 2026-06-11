import api from '@/lib/api'
import { useSettledQuery } from '@/lib/useSettledQuery'
import { useMutation } from '@tanstack/react-query'
import type { InvitationInviteeView, InvitationOwnerView, InvitationRole, Member } from './types'

export const useWorkspaceInvitations = (workspaceId: string, enabled = true) =>
    useSettledQuery({
        queryKey: ['workspaces', workspaceId, 'invitations'],
        queryFn: () => api.get<InvitationOwnerView[]>(`/workspaces/${workspaceId}/invitations`),
        enabled,
    })

export const useCreateInvitation = (workspaceId: string) =>
    useMutation({
        mutationFn: (data: { email: string; role: InvitationRole }) =>
            api.post<InvitationOwnerView>(`/workspaces/${workspaceId}/invitations`, data),
    })

export const useCancelInvitation = (workspaceId: string) =>
    useMutation({
        mutationFn: (invitationId: string) =>
            api.delete<null>(`/workspaces/${workspaceId}/invitations/${invitationId}`),
    })

export const useResendInvitation = (workspaceId: string) =>
    useMutation({
        mutationFn: (invitationId: string) =>
            api.post<null>(`/workspaces/${workspaceId}/invitations/${invitationId}/resend`, {}),
    })

export const useWorkspaceMembers = (workspaceId: string) =>
    useSettledQuery({
        queryKey: ['workspaces', workspaceId, 'members'],
        queryFn: () => api.get<Member[]>(`/workspaces/${workspaceId}/members`),
    })

export const useChangeMemberRole = (workspaceId: string) =>
    useMutation({
        mutationFn: ({ userId, role }: { userId: string; role: string }) =>
            api.patch<Member>(`/workspaces/${workspaceId}/members/${userId}`, { role }),
    })

export const useRemoveMember = (workspaceId: string) =>
    useMutation({
        mutationFn: (userId: string) =>
            api.delete<null>(`/workspaces/${workspaceId}/members/${userId}`),
    })

export const useLeaveWorkspace = (workspaceId: string) =>
    useMutation({
        mutationFn: () => api.delete<null>(`/workspaces/${workspaceId}/members/me`),
    })

export const useMyInvitations = () =>
    useSettledQuery({
        queryKey: ['invitations'],
        queryFn: () => api.get<InvitationInviteeView[]>('/invitations'),
    })

export const useAcceptInvitation = () =>
    useMutation({
        mutationFn: (invitationId: string) => api.put<null>(`/invitations/${invitationId}/accept`),
    })

export const useDeclineInvitation = () =>
    useMutation({
        mutationFn: (invitationId: string) =>
            api.delete<null>(`/invitations/${invitationId}/decline`),
    })
