import ApiError from '@/lib/ApiError'
import { useQueryClient } from '@tanstack/react-query'
import { useNavigate } from 'react-router'
import { toast } from 'sonner'
import {
    useChangeMemberRole,
    useLeaveWorkspace,
    useRemoveMember,
    useWorkspaceMembers,
} from '../queries'
import type { AssignableMemberRole } from '../types'

export const useMemberList = (workspaceId: string) => {
    const { data: members, isLoading } = useWorkspaceMembers(workspaceId)
    const { mutate: changeRole, isPending: isChanging } = useChangeMemberRole(workspaceId)
    const { mutate: removeMember, isPending: isRemoving } = useRemoveMember(workspaceId)
    const { mutate: leave, isPending: isLeaving } = useLeaveWorkspace(workspaceId)
    const queryClient = useQueryClient()
    const navigate = useNavigate()

    const handleRoleChange = (userId: string, role: AssignableMemberRole) => {
        changeRole(
            { userId, role },
            {
                onSuccess: () => {
                    void queryClient.invalidateQueries({
                        queryKey: ['workspaces', workspaceId, 'members'],
                    })
                },
                onError: (err) => {
                    if (err instanceof ApiError) toast.error(err.message)
                },
            },
        )
    }

    const handleRemove = (userId: string) => {
        removeMember(userId, {
            onSuccess: () => {
                void queryClient.invalidateQueries({
                    queryKey: ['workspaces', workspaceId, 'members'],
                })
                toast.success('Member removed')
            },
            onError: (err) => {
                if (err instanceof ApiError) toast.error(err.message)
            },
        })
    }

    const handleLeave = () => {
        leave(undefined, {
            onSuccess: () => {
                void queryClient.invalidateQueries({ queryKey: ['workspaces'] })
                toast.success('Left workspace')
                void navigate('/')
            },
            onError: (err) => {
                if (err instanceof ApiError) toast.error(err.message)
            },
        })
    }

    return {
        members,
        isLoading,
        isChanging,
        isRemoving,
        isLeaving,
        handleRoleChange,
        handleRemove,
        handleLeave,
    }
}
