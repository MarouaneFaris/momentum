type Props = { size: 'sm' | 'lg' }

export function MomentumLogo({ size }: Props) {
    const isLg = size === 'lg'
    return (
        <div className={`flex items-center ${isLg ? 'gap-3' : 'gap-2'}`}>
            <div
                className={`flex flex-col ${isLg ? 'gap-[5px]' : 'gap-[3px]'}`}
                style={{ transform: 'skewX(-12deg)' }}
            >
                <div
                    data-testid="logo-stripe"
                    className={`rounded-[2px] bg-primary opacity-25 ${isLg ? 'h-[5px] w-[28px]' : 'h-[3px] w-[14px]'}`}
                />
                <div
                    data-testid="logo-stripe"
                    className={`rounded-[2px] bg-primary opacity-55 ${isLg ? 'h-[5px] w-[28px]' : 'h-[3px] w-[14px]'}`}
                />
                <div
                    data-testid="logo-stripe"
                    className={`rounded-[2px] bg-primary ${isLg ? 'h-[5px] w-[28px]' : 'h-[3px] w-[14px]'}`}
                />
            </div>
            <span
                className={
                    isLg
                        ? 'text-3xl font-semibold tracking-tighter'
                        : 'text-sm font-semibold tracking-tight'
                }
            >
                <span className="text-primary">m</span>
                <span className="text-foreground">omentum</span>
            </span>
        </div>
    )
}
