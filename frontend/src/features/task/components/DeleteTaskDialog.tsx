import { ConfirmDialog } from '@/components/ConfirmDialog'
import type { Task } from '../types'

type Props = {
    open: boolean
    onOpenChange: (open: boolean) => void
    task: Task | null
    isPending: boolean
    onConfirm: () => void
}

export function DeleteTaskDialog({ open, onOpenChange, task, isPending, onConfirm }: Props) {
    const description = task ? (
        <>
            Delete <span className="text-foreground font-medium">{task.title}</span>? This cannot be
            undone.
        </>
    ) : (
        'This cannot be undone.'
    )

    return (
        <ConfirmDialog
            open={open}
            onOpenChange={onOpenChange}
            title="Delete task"
            description={description}
            confirmLabel={isPending ? 'Deleting…' : 'Delete'}
            onConfirm={onConfirm}
            isPending={isPending}
        />
    )
}
