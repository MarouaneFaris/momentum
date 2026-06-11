import { cn } from '@/lib/utils'
import type { TaskStats } from '../types'

type StatCardProps = {
    label: string
    value: number
    className?: string
}

function StatCard({ label, value, className }: StatCardProps) {
    return (
        <div className={cn('bg-muted rounded-lg px-3.5 py-3 md:px-4 md:py-3.5', className)}>
            <div className="text-[22px] leading-none font-semibold tracking-[-0.5px]">{value}</div>
            <div className="text-muted-foreground mt-1 text-[10px] font-medium tracking-[0.06em] uppercase">
                {label}
            </div>
        </div>
    )
}

type StatsRowProps = {
    stats: TaskStats
    isMobile?: boolean
}

export function StatsRow({ stats, isMobile = false }: StatsRowProps) {
    if (isMobile) {
        return (
            <div className="grid grid-cols-2 gap-1.5">
                <StatCard label="Open tasks" value={stats.open} />
                <StatCard label="Done this week" value={stats.done_this_week} />
            </div>
        )
    }

    return (
        <div className="grid grid-cols-3 gap-2.5">
            <StatCard label="Open tasks" value={stats.open} />
            <StatCard label="In progress" value={stats.in_progress} />
            <StatCard label="Done this week" value={stats.done_this_week} />
        </div>
    )
}
