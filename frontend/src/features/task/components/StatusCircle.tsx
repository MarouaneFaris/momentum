import { cn } from '@/lib/utils'
import type { TaskStatus } from '../types'
import { STATUS_CIRCLE_CLASSES } from '../taskStatus'

export function StatusCircle({ status }: { status: TaskStatus }) {
    return (
        <div
            className={cn('h-4 w-4 shrink-0 rounded-full border', STATUS_CIRCLE_CLASSES[status])}
        />
    )
}
