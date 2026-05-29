import { Button } from '@/components/ui/button'
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import ApiError from '@/lib/ApiError'
import { zodResolver } from '@hookform/resolvers/zod'
import { useForm } from 'react-hook-form'
import { useNavigate } from 'react-router'
import { toast } from 'sonner'
import z from 'zod'
import { useCreateWorkspace } from '../queries'
import { workspaceStorage } from '../workspaceStorage'

const schema = z.object({
    name: z.string().min(1, 'Name is required').max(64, 'Name must be 64 characters or fewer'),
})

type FormValues = z.infer<typeof schema>

type Props = {
    open: boolean
    onOpenChange: (open: boolean) => void
}

export function CreateWorkspaceModal({ open, onOpenChange }: Props) {
    const { mutate, isPending } = useCreateWorkspace()
    const { write } = workspaceStorage
    const navigate = useNavigate()

    const {
        register,
        handleSubmit,
        reset,
        formState: { errors },
    } = useForm<FormValues>({ resolver: zodResolver(schema) })

    const onSubmit = (values: FormValues) => {
        mutate(values, {
            onSuccess: (workspace) => {
                if (workspace) {
                    write(workspace.id)
                    onOpenChange(false)
                    reset()
                    void navigate(`/workspaces/${workspace.id}/dashboard`)
                }
            },
            onError: (error) => {
                if (error instanceof ApiError) {
                    toast.error(error.message)
                }
            },
        })
    }

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Create workspace</DialogTitle>
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
