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
        <div className="flex [scrollbar-width:none] items-center gap-2 overflow-x-auto [&::-webkit-scrollbar]:hidden">
            {options.map((opt) => (
                <Button
                    key={opt.value}
                    size="sm"
                    variant="ghost"
                    onClick={() => onChange(opt.value)}
                    className={cn(
                        'h-7 cursor-pointer rounded-full border px-2.5 text-xs font-medium whitespace-nowrap',
                        opt.value === value
                            ? 'border-primary/30 bg-primary/10 text-primary hover:bg-primary/15 hover:text-primary dark:border-primary/50 dark:bg-primary/20 dark:hover:bg-primary/25'
                            : 'border-border bg-muted text-muted-foreground hover:bg-muted hover:text-foreground dark:bg-transparent dark:hover:bg-transparent',
                    )}
                >
                    {opt.label}
                </Button>
            ))}
        </div>
    )
}
