import z from 'zod'
import type { TaskStatus } from './types'

export const TASK_STATUSES = ['todo', 'in-progress', 'done'] as const

export const taskStatusSchema = z.enum(TASK_STATUSES)

export const STATUS_LABELS: Record<TaskStatus, string> = {
    todo: 'To do',
    'in-progress': 'In progress',
    done: 'Done',
}

export const STATUS_BADGE_CLASSES: Record<TaskStatus, string> = {
    todo: 'bg-muted text-muted-foreground border-border',
    'in-progress': 'bg-primary/10 text-primary border-primary/25',
    done: 'bg-green-100 text-green-700 border-green-200 dark:bg-green-900/20 dark:text-green-400 dark:border-green-800/30',
}

export const STATUS_CIRCLE_CLASSES: Record<TaskStatus, string> = {
    todo: 'border-border bg-muted',
    'in-progress': 'border-primary/40 bg-primary/20',
    done: 'border-green-400 bg-green-300 dark:border-green-600 dark:bg-green-700',
}

export const STATUS_OPTIONS: { value: TaskStatus; label: string }[] = TASK_STATUSES.map(
    (status) => ({ value: status, label: STATUS_LABELS[status] }),
)
