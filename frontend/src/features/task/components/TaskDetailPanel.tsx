import { Badge } from '@/components/ui/badge'
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import type { TaskDetail, TaskStatus } from '../types'

function StatusBadge({ status }: { status: TaskStatus }) {
    const className = {
        todo: 'bg-muted text-muted-foreground border-border',
        'in-progress': 'bg-primary/10 text-primary border-primary/25',
        done: 'bg-green-100 text-green-700 border-green-200 dark:bg-green-900/20 dark:text-green-400 dark:border-green-800/30',
    }[status]

    const label = { todo: 'Todo', 'in-progress': 'In progress', done: 'Done' }[status]

    return (
        <Badge variant="outline" className={className}>
            {label}
        </Badge>
    )
}

function UserSummary({ user }: { user: { id: string; name: string } }) {
    const initials = user.name
        .split(' ')
        .map((p) => p[0])
        .join('')
        .slice(0, 2)
        .toUpperCase()

    return (
        <div className="flex items-center gap-1.5">
            <div className="w-6 h-6 rounded-full bg-primary/15 border border-primary/30 flex items-center justify-center text-[9px] font-semibold text-primary flex-shrink-0">
                {initials}
            </div>
            <span className="text-sm text-foreground">{user.name}</span>
        </div>
    )
}

function Field({ label, children }: { label: string; children: React.ReactNode }) {
    return (
        <div className="flex flex-col gap-1">
            <span className="text-[11px] font-medium text-muted-foreground uppercase tracking-wide">
                {label}
            </span>
            {children}
        </div>
    )
}

function formatDate(iso: string): string {
    return new Date(iso).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    })
}

type Props = {
    task: TaskDetail | null
    open: boolean
    onClose: () => void
}

export default function TaskDetailPanel({ task, open, onClose }: Props) {
    return (
        <Dialog open={open} onOpenChange={(isOpen) => !isOpen && onClose()}>
            <DialogContent className="max-w-lg">
                {task ? (
                    <>
                        <DialogHeader>
                            <DialogTitle className="text-base font-semibold leading-snug pr-6">
                                {task.title}
                            </DialogTitle>
                        </DialogHeader>

                        <div className="flex flex-col gap-4 mt-1">
                            <Field label="Status">
                                <StatusBadge status={task.status} />
                            </Field>

                            {task.description && (
                                <Field label="Description">
                                    <p className="text-sm text-foreground whitespace-pre-wrap">
                                        {task.description}
                                    </p>
                                </Field>
                            )}

                            <Field label="Creator">
                                <UserSummary user={task.creator} />
                            </Field>

                            <Field label="Assignee">
                                {task.assignee ? (
                                    <UserSummary user={task.assignee} />
                                ) : (
                                    <span className="text-sm text-muted-foreground">—</span>
                                )}
                            </Field>

                            <div className="grid grid-cols-2 gap-4">
                                <Field label="Created">
                                    <span className="text-sm text-foreground">
                                        {formatDate(task.createdAt)}
                                    </span>
                                </Field>
                                <Field label="Updated">
                                    <span className="text-sm text-foreground">
                                        {formatDate(task.updatedAt)}
                                    </span>
                                </Field>
                            </div>
                        </div>
                    </>
                ) : (
                    <div className="h-40" />
                )}
            </DialogContent>
        </Dialog>
    )
}
