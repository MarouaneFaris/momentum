import type { ProjectColorKey } from './types'

const PROJECT_COLOR_MAP: Record<ProjectColorKey, string> = {
    blue: 'oklch(0.488 0.243 264.376)',
    green: 'oklch(0.55 0.15 145)',
    amber: 'oklch(0.6 0.15 55)',
    red: 'oklch(0.55 0.18 20)',
    purple: 'oklch(0.55 0.15 300)',
    neutral: 'oklch(0.6 0.15 200)',
}

export const PROJECT_COLOR_KEYS = Object.keys(PROJECT_COLOR_MAP) as ProjectColorKey[]

export function projectColorValue(key: ProjectColorKey): string {
    return PROJECT_COLOR_MAP[key]
}
