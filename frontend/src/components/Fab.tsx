import type { LucideIcon } from 'lucide-react'

type Props = {
    onClick: () => void
    icon: LucideIcon
    hidden?: boolean
}

export function Fab({ onClick, icon: Icon, hidden }: Props) {
    if (hidden) return null

    return (
        <button
            onClick={onClick}
            className="bg-primary text-primary-foreground fixed z-50 flex h-[38px] w-[38px] items-center justify-center rounded-full shadow-[0_2px_8px_oklch(0.488_0.243_264.376/0.4)]"
            style={{ bottom: 70, right: 14 }}
            aria-label="Action"
        >
            <Icon size={18} />
        </button>
    )
}
