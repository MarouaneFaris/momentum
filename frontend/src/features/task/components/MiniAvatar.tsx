export function MiniAvatar({ name }: { name: string }) {
    const initials = name
        .split(' ')
        .map((p) => p[0])
        .join('')
        .slice(0, 2)
        .toUpperCase()

    return (
        <div className="bg-primary/15 border-primary/30 text-primary flex h-5 w-5 shrink-0 items-center justify-center rounded-full border text-[8px] font-semibold">
            {initials}
        </div>
    )
}
