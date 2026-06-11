import { Badge } from '@/components/ui/badge'
import { cn } from '@/lib/utils'
import type { TaskStatus } from '../types'

export function StatusBadge({ status }: { status: TaskStatus }) {
    const className = {
        todo: 'bg-muted text-muted-foreground border-border',
        'in-progress': 'bg-primary/10 text-primary border-primary/25',
        done: 'bg-green-100 text-green-700 border-green-200 dark:bg-green-900/20 dark:text-green-400 dark:border-green-800/30',
    }[status]

    const label = { todo: 'To do', 'in-progress': 'In progress', done: 'Done' }[status]

    return (
        <Badge variant="outline" className={cn('shrink-0 text-[10px]', className)}>
            {label}
        </Badge>
    )
}
