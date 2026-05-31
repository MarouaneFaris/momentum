import { Button } from '@/components/ui/button'
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { useCreateWorkspaceForm } from '../hooks/useCreateWorkspaceForm'

type Props = {
    open: boolean
    onOpenChange: (open: boolean) => void
}

export function CreateWorkspaceModal({ open, onOpenChange }: Props) {
    const { form, isPending, onSubmit } = useCreateWorkspaceForm(() => onOpenChange(false))
    const {
        register,
        handleSubmit,
        reset,
        formState: { errors },
    } = form

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Create workspace</DialogTitle>
                    <DialogDescription>Enter a name for your new workspace.</DialogDescription>
                </DialogHeader>
                <form id="create-workspace-form" onSubmit={(e) => void handleSubmit(onSubmit)(e)}>
                    <div className="grid gap-2">
                        <Label htmlFor="workspace-name">Name</Label>
                        <Input
                            id="workspace-name"
                            placeholder="My workspace"
                            autoFocus
                            {...register('name')}
                        />
                        {errors.name && (
                            <p className="text-sm text-destructive">{errors.name.message}</p>
                        )}
                    </div>
                </form>
                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        onClick={() => {
                            onOpenChange(false)
                            reset()
                        }}
                    >
                        Cancel
                    </Button>
                    <Button type="submit" form="create-workspace-form" disabled={isPending}>
                        Create
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    )
}
