import { Moon, Sun } from 'lucide-react'
import { useTheme } from 'next-themes'
import { Button } from './ui/button'

export const ThemeToggle = () => {
    const { resolvedTheme, setTheme } = useTheme()

    const toggle = () => setTheme(resolvedTheme === 'dark' ? 'light' : 'dark')

    return (
        <Button variant="ghost" size="icon" onClick={toggle}>
            {resolvedTheme === 'dark' ? (
                <Sun className="h-[1.2rem] w-[1.2rem]" />
            ) : (
                <Moon className="h-[1.2rem] w-[1.2rem]" />
            )}
            <span className="sr-only">Toggle theme</span>
        </Button>
    )
}
