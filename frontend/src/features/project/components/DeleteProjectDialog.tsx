import { Button } from '@/components/ui/button'
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog'
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
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete project</DialogTitle>
                    <DialogDescription>
                        Delete <strong>{project.name}</strong>? This cannot be undone.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        onClick={() => onOpenChange(false)}
                        disabled={isPending}
                    >
                        Cancel
                    </Button>
                    <Button
                        type="button"
                        variant="destructive"
                        onClick={confirm}
                        disabled={isPending}
                    >
                        Delete
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    )
}
