import { useQueryClient } from '@tanstack/react-query'
import { useState } from 'react'
import { useNavigate } from 'react-router'
import { toast } from 'sonner'
import { useDeleteWorkspace, useWorkspaces } from '../queries'
import type { Workspace } from '../types'
import { workspaceStorage } from '../workspaceStorage'

export const useDeleteWorkspaceAction = (workspace: Workspace) => {
    const [confirmation, setConfirmation] = useState('')
    const { mutate, isPending } = useDeleteWorkspace()
    const { data: workspaces } = useWorkspaces()
    const navigate = useNavigate()
    const queryClient = useQueryClient()

    const isConfirmed = confirmation === workspace.name

    const handleDelete = () => {
        mutate(undefined, {
            onSuccess: () => {
                void queryClient.invalidateQueries({ queryKey: ['workspaces'] })
                localStorage.removeItem('lastVisitedWorkspaceId')
                const remaining = workspaces?.filter((w) => w.id !== workspace.id) ?? []
                if (remaining.length > 0) {
                    workspaceStorage.write(remaining[0].id)
                    void navigate(`/workspaces/${remaining[0].id}/dashboard`)
                } else {
                    void navigate('/')
                }
            },
            onError: () => {
                toast.error('Failed to delete workspace')
            },
        })
    }

    return { confirmation, setConfirmation, isConfirmed, isPending, handleDelete }
}
