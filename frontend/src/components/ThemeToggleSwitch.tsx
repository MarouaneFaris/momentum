import { Switch } from '@/components/ui/switch'
import { Moon, Sun } from 'lucide-react'
import { useTheme } from 'next-themes'

export const ThemeToggleSwitch = () => {
    const { resolvedTheme, setTheme } = useTheme()
    const isDark = resolvedTheme === 'dark'

    return (
        <div className="flex items-center gap-2">
            <Sun className="text-muted-foreground h-4 w-4" />
            <Switch
                checked={isDark}
                onCheckedChange={(checked) => setTheme(checked ? 'dark' : 'light')}
                aria-label="Toggle theme"
            />
            <Moon className="text-muted-foreground h-4 w-4" />
        </div>
    )
}
