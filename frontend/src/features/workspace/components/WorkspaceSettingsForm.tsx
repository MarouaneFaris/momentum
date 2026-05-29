import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import ApiError from '@/lib/ApiError'
import { zodResolver } from '@hookform/resolvers/zod'
import { useForm } from 'react-hook-form'
import { toast } from 'sonner'
import z from 'zod'
import { useRenameWorkspace } from '../queries'
import type { Workspace } from '../types'

const schema = z.object({
    name: z.string().min(1, 'Name is required').max(64, 'Name must be 64 characters or fewer'),
})

type FormValues = z.infer<typeof schema>

type Props = {
    workspace: Workspace
}

export function WorkspaceSettingsForm({ workspace }: Props) {
    const { mutate, isPending } = useRenameWorkspace(workspace.id)
    const isOwner = workspace.role === 'owner'

    const {
        register,
        handleSubmit,
        formState: { errors },
    } = useForm<FormValues>({
        resolver: zodResolver(schema),
        defaultValues: { name: workspace.name },
    })

    const onSubmit = (values: FormValues) => {
        mutate(values, {
            onSuccess: () => toast.success('Workspace renamed'),
            onError: (error) => {
                if (error instanceof ApiError) {
                    toast.error(error.message)
                }
            },
        })
    }

    if (!isOwner) {
        return (
            <div className="flex flex-col gap-2">
                <div className="grid gap-1">
                    <Label>Workspace name</Label>
                    <p className="text-sm">{workspace.name}</p>
                </div>
                <div className="grid gap-1">
                    <Label>Your role</Label>
                    <p className="text-sm capitalize">{workspace.role}</p>
                </div>
            </div>
        )
    }

    return (
        <form onSubmit={(e) => void handleSubmit(onSubmit)(e)} className="flex flex-col gap-4">
            <div className="grid gap-2">
                <Label htmlFor="workspace-name">Workspace name</Label>
                <Input id="workspace-name" {...register('name')} />
                {errors.name && (
                    <p className="text-sm text-destructive">{errors.name.message}</p>
                )}
            </div>
            <Button type="submit" className="w-fit" disabled={isPending}>
                Save changes
            </Button>
        </form>
    )
}
