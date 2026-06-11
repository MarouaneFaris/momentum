const PROJECT_COLORS = [
    'oklch(0.488 0.243 264.376)',
    'oklch(0.55 0.15 145)',
    'oklch(0.6 0.15 55)',
    'oklch(0.55 0.18 20)',
    'oklch(0.55 0.15 300)',
    'oklch(0.6 0.15 200)',
]

export function getProjectColor(id: string): string {
    const hash = id.split('').reduce((acc, c) => acc + c.charCodeAt(0), 0)
    return PROJECT_COLORS[hash % PROJECT_COLORS.length]
}
