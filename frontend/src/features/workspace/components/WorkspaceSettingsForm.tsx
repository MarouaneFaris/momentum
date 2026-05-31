import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { useRenameWorkspaceForm } from '../hooks/useRenameWorkspaceForm'
import type { Workspace } from '../types'

type Props = {
    workspace: Workspace
}

export function WorkspaceSettingsForm({ workspace }: Props) {
    const { form, isPending, onSubmit } = useRenameWorkspaceForm(workspace)
    const {
        register,
        handleSubmit,
        formState: { errors },
    } = form
    const isOwner = workspace.role === 'owner'

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
                {errors.name && <p className="text-sm text-destructive">{errors.name.message}</p>}
            </div>
            <Button type="submit" className="w-fit" disabled={isPending}>
                Save changes
            </Button>
        </form>
    )
}
