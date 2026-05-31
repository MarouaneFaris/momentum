import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { useDeleteWorkspaceAction } from '../hooks/useDeleteWorkspaceAction'
import type { Workspace } from '../types'

type Props = {
    workspace: Workspace
}

export function DeleteWorkspaceZone({ workspace }: Props) {
    const { confirmation, setConfirmation, isConfirmed, isPending, handleDelete } =
        useDeleteWorkspaceAction(workspace)

    return (
        <div className="flex flex-col gap-4 rounded-lg border border-destructive/50 p-4">
            <div>
                <h2 className="font-semibold text-destructive">Delete workspace</h2>
                <p className="text-sm text-muted-foreground mt-1">
                    This will permanently delete <strong>{workspace.name}</strong> and all of its
                    projects, tasks, memberships, and invitations. This action cannot be undone.
                </p>
            </div>
            <div className="grid gap-2">
                <Label htmlFor="delete-confirm">
                    Type <strong>{workspace.name}</strong> to confirm
                </Label>
                <Input
                    id="delete-confirm"
                    value={confirmation}
                    onChange={(e) => setConfirmation(e.target.value)}
                    placeholder={workspace.name}
                />
            </div>
            <Button
                variant="destructive"
                disabled={!isConfirmed || isPending}
                onClick={handleDelete}
                className="w-fit"
            >
                Delete workspace
            </Button>
        </div>
    )
}
