import { Button } from '@/components/ui/button'
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog'
import type { Task } from '../types'

type Props = {
    open: boolean
    onOpenChange: (open: boolean) => void
    task: Task | null
    isPending: boolean
    onConfirm: () => void
}

export function DeleteTaskDialog({ open, onOpenChange, task, isPending, onConfirm }: Props) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-w-sm">
                <DialogHeader>
                    <DialogTitle>Delete task</DialogTitle>
                    <DialogDescription>
                        {task ? (
                            <>
                                Delete{' '}
                                <span className="text-foreground font-medium">{task.title}</span>?
                                This cannot be undone.
                            </>
                        ) : (
                            'This cannot be undone.'
                        )}
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button
                        variant="outline"
                        onClick={() => onOpenChange(false)}
                        disabled={isPending}
                    >
                        Cancel
                    </Button>
                    <Button variant="destructive" onClick={onConfirm} disabled={isPending}>
                        {isPending ? 'Deleting…' : 'Delete'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    )
}
