import type { LucideIcon } from 'lucide-react'
import type { ReactNode } from 'react'

type EmptyStateProps = {
    icon?: LucideIcon
    title: string
    description: string
    action?: ReactNode
}

export function EmptyState({ icon: Icon, title, description, action }: EmptyStateProps) {
    return (
        <div className="flex flex-col items-center gap-4 py-16 text-center">
            {Icon && (
                <div className="bg-muted flex size-12 items-center justify-center rounded-full">
                    <Icon className="text-muted-foreground size-5" />
                </div>
            )}
            <div className="flex flex-col gap-1">
                <p className="text-foreground text-sm font-medium">{title}</p>
                <p className="text-muted-foreground text-xs">{description}</p>
            </div>
            {action}
        </div>
    )
}
