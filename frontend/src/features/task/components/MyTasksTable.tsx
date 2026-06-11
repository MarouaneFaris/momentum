import { ClipboardList } from 'lucide-react'
import type { Task } from '../types'
import { MiniAvatar } from './MiniAvatar'
import { StatusBadge } from './StatusBadge'
import { StatusCircle } from './StatusCircle'

function formatDate(iso: string) {
    return new Date(iso).toLocaleDateString(undefined, { month: 'short', day: 'numeric' })
}

function MyTaskRow({ task }: { task: Task }) {
    return (
        <div className="border-border bg-card flex items-center gap-2.5 rounded-[var(--radius)] border px-3 py-2.5">
            <StatusCircle status={task.status} />
            <span className="text-foreground min-w-0 flex-1 truncate text-sm leading-snug font-medium">
                {task.title}
            </span>
            <StatusBadge status={task.status} />
            {task.assignee && <MiniAvatar name={task.assignee.name} />}
            <span className="text-muted-foreground shrink-0 text-[11px]">
                {formatDate(task.createdAt)}
            </span>
        </div>
    )
}

type MyTasksTableProps = {
    tasks: Task[]
    emptyMessage?: string
}

export function MyTasksTable({
    tasks,
    emptyMessage = 'No tasks assigned to you',
}: MyTasksTableProps) {
    if (tasks.length === 0) {
        return (
            <div className="flex flex-col items-center gap-3 py-10 text-center">
                <ClipboardList className="text-border size-8" />
                <p className="text-muted-foreground text-sm">{emptyMessage}</p>
            </div>
        )
    }

    return (
        <div className="flex flex-col gap-1.5">
            {tasks.map((task) => (
                <MyTaskRow key={task.id} task={task} />
            ))}
        </div>
    )
}
