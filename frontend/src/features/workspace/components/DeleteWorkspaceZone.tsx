import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { useLastVisitedWorkspace } from '../hooks/useLastVisitedWorkspace'
import { useDeleteWorkspace, useWorkspaces } from '../queries'
import type { Workspace } from '../types'
import { useState } from 'react'
import { useNavigate } from 'react-router'
import { toast } from 'sonner'

type Props = {
    workspace: Workspace
}

export function DeleteWorkspaceZone({ workspace }: Props) {
    const [confirmation, setConfirmation] = useState('')
    const { mutate, isPending } = useDeleteWorkspace(workspace.id)
    const { data: workspaces } = useWorkspaces()
    const { write } = useLastVisitedWorkspace()
    const navigate = useNavigate()

    const isConfirmed = confirmation === workspace.name

    const handleDelete = () => {
        mutate(undefined, {
            onSuccess: () => {
                localStorage.removeItem('lastVisitedWorkspaceId')
                const remaining = workspaces?.filter((w) => w.id !== workspace.id) ?? []
                if (remaining.length > 0) {
                    write(remaining[0].id)
                    void navigate(`/workspaces/${remaining[0].id}/dashboard`)
                } else {
                    void navigate('/')
                }
            },
            onError: () => {
                toast.error('Failed to delete workspace')
            },
        })
    }

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
