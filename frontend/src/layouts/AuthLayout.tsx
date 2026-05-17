import { Button } from '@/components/ui/button'
import { Moon, Sun } from 'lucide-react'
import { useEffect, useState } from 'react'
import { Outlet } from 'react-router'

export default function AuthLayout() {
    const [isDarkMode, setIsDarkMode] = useState(localStorage.getItem('isDarkMode') === 'enabled')

    useEffect(() => {
        if (isDarkMode) {
            document.querySelector('body')?.classList.add('dark')
        } else {
            document.querySelector('body')?.classList.remove('dark')
        }

        localStorage.setItem('isDarkMode', isDarkMode ? 'enabled' : 'disabled')
    }, [isDarkMode])

    return (
        <>
            <Button
                type="button"
                size="icon"
                variant="outline"
                onClick={() => setIsDarkMode(!isDarkMode)}
            >
                {isDarkMode ? <Sun /> : <Moon />}
            </Button>
            <main className="flex justify-center mt-32">
                <Outlet />
            </main>
        </>
    )
}
