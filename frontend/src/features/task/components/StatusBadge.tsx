import { Badge } from '@/components/ui/badge'
import { cn } from '@/lib/utils'
import type { TaskStatus } from '../types'
import { STATUS_BADGE_CLASSES, STATUS_LABELS } from '../taskStatus'

export function StatusBadge({ status }: { status: TaskStatus }) {
    return (
        <Badge
            variant="outline"
            className={cn('shrink-0 text-[10px]', STATUS_BADGE_CLASSES[status])}
        >
            {STATUS_LABELS[status]}
        </Badge>
    )
}
