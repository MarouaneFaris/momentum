import { cn } from '@/lib/utils'

type Props = {
    name: string
    size?: 'sm' | 'md'
    className?: string
}

function getInitials(name: string): string {
    return name
        .split(' ')
        .map((w) => w[0])
        .join('')
        .slice(0, 2)
        .toUpperCase()
}

export function UserAvatar({ name, size = 'md', className }: Props) {
    const isEmpty = !name.trim()
    const initials = isEmpty ? '?' : getInitials(name)

    return (
        <div
            className={cn(
                'flex shrink-0 items-center justify-center rounded-full',
                size === 'sm'
                    ? 'bg-primary/15 border-primary/30 text-primary h-5 w-5 border text-[8px] font-semibold'
                    : isEmpty
                      ? 'bg-muted border-border text-muted-foreground h-8 w-8 border text-xs font-medium'
                      : 'bg-primary/10 text-primary h-8 w-8 text-xs font-medium',
                className,
            )}
        >
            {initials}
        </div>
    )
}
