import { cn } from '@/lib/utils'
import type { TaskStatus } from '../types'

export function StatusCircle({ status }: { status: TaskStatus }) {
    const className = {
        todo: 'border-border bg-muted',
        'in-progress': 'border-primary/40 bg-primary/20',
        done: 'border-green-400 bg-green-300 dark:border-green-600 dark:bg-green-700',
    }[status]

    return <div className={cn('h-4 w-4 shrink-0 rounded-full border', className)} />
}
