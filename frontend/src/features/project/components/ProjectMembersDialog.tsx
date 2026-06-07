import { Button } from '@/components/ui/button'
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'
import { UserMinusIcon } from 'lucide-react'
import { useProjectMembersDialog } from '../hooks/useProjectMembersDialog'
import type { Project } from '../types'

type Props = {
    open: boolean
    onOpenChange: (open: boolean) => void
    workspaceId: string
    project: Project
}

export function ProjectMembersDialog({ open, onOpenChange, workspaceId, project }: Props) {
    const {
        members,
        availableGuests,
        selectedUserId,
        setSelectedUserId,
        handleAssign,
        handleRemove,
        isAssignPending,
        isRemovePending,
    } = useProjectMembersDialog(workspaceId, project)

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Members — {project.name}</DialogTitle>
                </DialogHeader>

                <div className="flex flex-col gap-4">
                    {members.length > 0 ? (
                        <ul className="flex flex-col gap-2">
                            {members.map((member) => (
                                <li
                                    key={member.id}
                                    className="flex items-center justify-between rounded-md border px-3 py-2 text-sm"
                                >
                                    <div className="flex flex-col">
                                        <span className="font-medium">{member.name}</span>
                                        <span className="text-muted-foreground text-xs">
                                            {member.email}
                                        </span>
                                    </div>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        disabled={isRemovePending}
                                        onClick={() => handleRemove(member.id)}
                                        aria-label={`Remove ${member.name}`}
                                    >
                                        <UserMinusIcon className="size-4" />
                                    </Button>
                                </li>
                            ))}
                        </ul>
                    ) : (
                        <p className="text-muted-foreground text-sm">No guests assigned yet.</p>
                    )}

                    {availableGuests.length > 0 && (
                        <div className="flex gap-2">
                            <Select value={selectedUserId} onValueChange={setSelectedUserId}>
                                <SelectTrigger className="flex-1">
                                    <SelectValue placeholder="Add a guest..." />
                                </SelectTrigger>
                                <SelectContent>
                                    {availableGuests.map((g) => (
                                        <SelectItem key={g.id} value={g.id}>
                                            {g.name} — {g.email}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Button
                                type="button"
                                disabled={!selectedUserId || isAssignPending}
                                onClick={handleAssign}
                            >
                                Add
                            </Button>
                        </div>
                    )}
                </div>
            </DialogContent>
        </Dialog>
    )
}
