import { Button } from '@/components/ui/button'
import { cn } from '@/lib/utils'

type Option = {
    label: string
    value: string
}

type Props = {
    options: Option[]
    value: string
    onChange: (value: string) => void
}

export function FilterChips({ options, value, onChange }: Props) {
    return (
        <div className="flex [scrollbar-width:none] items-center gap-1.5 overflow-x-auto md:gap-2 [&::-webkit-scrollbar]:hidden">
            {options.map((opt) => (
                <Button
                    key={opt.value}
                    variant="ghost"
                    onClick={() => onChange(opt.value)}
                    className={cn(
                        'h-auto cursor-pointer rounded-full border px-1.5 py-0.5 text-[9px] font-medium whitespace-nowrap md:h-7 md:px-2.5 md:text-[11px]',
                        opt.value === value
                            ? '!border-primary/30 !bg-primary/10 text-primary hover:!bg-primary/15 hover:text-primary dark:!border-primary/50 dark:!bg-primary/20 dark:hover:!bg-primary/25'
                            : '!border-border !bg-muted text-muted-foreground hover:!bg-muted hover:text-foreground dark:!bg-transparent dark:hover:!bg-transparent',
                    )}
                >
                    {opt.label}
                </Button>
            ))}
        </div>
    )
}
