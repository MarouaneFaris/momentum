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
        <div className="flex [scrollbar-width:none] gap-0.5 overflow-x-auto [&::-webkit-scrollbar]:hidden">
            {options.map((opt) => (
                <button
                    key={opt.value}
                    onClick={() => onChange(opt.value)}
                    className={cn(
                        'rounded-full border px-2.5 py-0.5 text-[10px] font-medium whitespace-nowrap',
                        opt.value === value
                            ? 'border-primary/30 bg-primary/10 text-primary'
                            : 'border-border bg-muted text-muted-foreground',
                    )}
                >
                    {opt.label}
                </button>
            ))}
        </div>
    )
}
