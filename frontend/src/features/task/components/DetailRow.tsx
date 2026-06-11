export function DetailRow({ label, children }: { label: string; children: React.ReactNode }) {
    return (
        <div className="flex items-center gap-2.5">
            <span className="text-muted-foreground w-20 shrink-0 text-[11px] font-medium tracking-[0.05em] uppercase">
                {label}
            </span>
            <div className="text-foreground flex items-center gap-1.5 text-[13px]">{children}</div>
        </div>
    )
}
