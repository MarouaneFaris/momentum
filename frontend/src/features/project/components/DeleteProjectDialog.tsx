import { Button } from '@/components/ui/button'
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog'
import { useQueryClient } from '@tanstack/react-query'
import { useDeleteProject } from '../queries'
import type { Project } from '../types'

type Props = {
    open: boolean
    onOpenChange: (open: boolean) => void
    workspaceId: string
    project: Project
}

export function DeleteProjectDialog({ open, onOpenChange, workspaceId, project }: Props) {
    const queryClient = useQueryClient()
    const { mutate, isPending } = useDeleteProject(workspaceId, project.id)

    function handleConfirm() {
        mutate(undefined, {
            onSuccess: () => {
                queryClient.setQueryData<Project[]>(
                    ['workspaces', workspaceId, 'projects'],
                    (prev) => prev?.filter((p) => p.id !== project.id) ?? [],
                )
                onOpenChange(false)
            },
        })
    }

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
                        onClick={handleConfirm}
                        disabled={isPending}
                    >
                        Delete
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    )
}
