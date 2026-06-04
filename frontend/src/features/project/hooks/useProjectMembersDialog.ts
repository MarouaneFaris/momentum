import { useWorkspaceMembers } from '@/features/membership/queries'
import { useQueryClient } from '@tanstack/react-query'
import { useState } from 'react'
import { useAssignProjectMember, useProjectMembers, useRemoveProjectMember } from '../queries'
import type { Project } from '../types'

export function useProjectMembersDialog(workspaceId: string, project: Project) {
    const [selectedUserId, setSelectedUserId] = useState('')
    const queryClient = useQueryClient()
    const membersQueryKey = ['workspaces', workspaceId, 'projects', project.id, 'members']

    const { data: membersData } = useProjectMembers(workspaceId, project.id)
    const { data: workspaceMembersData } = useWorkspaceMembers(workspaceId)
    const assignMutation = useAssignProjectMember(workspaceId, project.id)
    const removeMutation = useRemoveProjectMember(workspaceId, project.id)

    const members = membersData ?? []
    const assignedIds = new Set(members.map((m) => m.id))
    const availableGuests = (workspaceMembersData ?? []).filter(
        (m) => m.role === 'guest' && !assignedIds.has(m.id),
    )

    function handleAssign() {
        if (!selectedUserId) return
        assignMutation.mutate(
            { userId: selectedUserId },
            {
                onSuccess: () => {
                    setSelectedUserId('')
                    void queryClient.invalidateQueries({ queryKey: membersQueryKey })
                },
            },
        )
    }

    function handleRemove(memberId: string) {
        removeMutation.mutate(memberId, {
            onSuccess: () => void queryClient.invalidateQueries({ queryKey: membersQueryKey }),
        })
    }

    return {
        members,
        availableGuests,
        selectedUserId,
        setSelectedUserId,
        handleAssign,
        handleRemove,
        isAssignPending: assignMutation.isPending,
        isRemovePending: removeMutation.isPending,
    }
}
