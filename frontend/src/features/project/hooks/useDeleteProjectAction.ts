import { useQueryClient } from '@tanstack/react-query'
import { useDeleteProject } from '../queries'
import type { Project } from '../types'

type Options = {
    workspaceId: string
    projectId: string
    onSuccess: () => void
}

export function useDeleteProjectAction({ workspaceId, projectId, onSuccess }: Options) {
    const queryClient = useQueryClient()
    const { mutate, isPending } = useDeleteProject(workspaceId, projectId)

    function confirm() {
        mutate(undefined, {
            onSuccess: () => {
                queryClient.setQueryData<Project[]>(
                    ['workspaces', workspaceId, 'projects'],
                    (prev) => prev?.filter((p) => p.id !== projectId) ?? [],
                )
                onSuccess()
            },
        })
    }

    return { confirm, isPending }
}
