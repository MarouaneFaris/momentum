import { ConfirmDialog } from '@/components/ConfirmDialog'
import { useDeleteProjectAction } from '../hooks/useDeleteProjectAction'
import type { Project } from '../types'

type Props = {
    open: boolean
    onOpenChange: (open: boolean) => void
    workspaceId: string
    project: Project
}

export function DeleteProjectDialog({ open, onOpenChange, workspaceId, project }: Props) {
    const { confirm, isPending } = useDeleteProjectAction({
        workspaceId,
        projectId: project.id,
        onSuccess: () => onOpenChange(false),
    })

    return (
        <ConfirmDialog
            open={open}
            onOpenChange={onOpenChange}
            title="Delete project"
            description={
                <>
                    Delete <strong>{project.name}</strong>? This cannot be undone.
                </>
            }
            confirmLabel="Delete"
            onConfirm={confirm}
            isPending={isPending}
        />
    )
}
