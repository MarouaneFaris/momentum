import { Button } from '@/components/ui/button'
import { UserAvatar } from '@/components/UserAvatar'
import type { Task } from '../types'
import { StatusBadge } from './StatusBadge'
import { StatusCircle } from './StatusCircle'

export function TaskRow({ task, onClick }: { task: Task; onClick: () => void }) {
    return (
        <Button
            variant="ghost"
            onClick={onClick}
            className="border-border bg-card active:bg-muted flex h-auto w-full items-center gap-2.5 rounded-[var(--radius)] border px-3 py-2.5 text-left transition-colors"
        >
            <StatusCircle status={task.status} />
            <span className="text-foreground flex-1 truncate text-sm leading-snug font-medium">
                {task.title}
            </span>
            <StatusBadge status={task.status} />
            {task.assignee && <UserAvatar name={task.assignee.name} size="sm" />}
        </Button>
    )
}
