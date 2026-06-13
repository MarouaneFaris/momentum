import { Button } from '@/components/ui/button'
import { ArrowLeft } from 'lucide-react'
import type { ReactNode } from 'react'
import { useNavigate } from 'react-router'

interface Props {
    title: string
    backHref?: string
    onBack?: () => void
    action?: ReactNode
    children: ReactNode
}

export function MobileLayout({ title, backHref, onBack, action, children }: Props) {
    const navigate = useNavigate()

    const handleBack = backHref ? () => void navigate(backHref) : onBack

    return (
        <div className="flex flex-col">
            <div className="bg-background border-border sticky top-0 z-10 flex items-center border-b px-4 py-3">
                {handleBack ? (
                    <Button
                        variant="ghost"
                        size="icon"
                        onClick={handleBack}
                        aria-label="Back"
                        className="-ml-1 h-8 w-8 shrink-0"
                    >
                        <ArrowLeft size={18} />
                    </Button>
                ) : (
                    <span className="w-8 shrink-0" aria-hidden />
                )}
                <span className="text-foreground flex-1 truncate text-center text-sm font-semibold">
                    {title}
                </span>
                {action ? (
                    <div className="shrink-0">{action}</div>
                ) : (
                    <span className="w-8 shrink-0" aria-hidden />
                )}
            </div>
            {children}
        </div>
    )
}
