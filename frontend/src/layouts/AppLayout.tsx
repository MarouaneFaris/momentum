import { Button } from '@/components/ui/button'
import { AuthContext } from '@/contexts/auth/AuthContext'
import { useLogoutAction } from '@/features/auth/hooks/useLogoutAction'
import { Moon, Sun } from 'lucide-react'
import { useContext, useEffect, useState } from 'react'
import { Navigate, Outlet } from 'react-router'

export default function AppLayout() {
    const auth = useContext(AuthContext)
    const [isDarkMode, setIsDarkMode] = useState(localStorage.getItem('isDarkMode') === 'enabled')
    const { handleOnLogout } = useLogoutAction()

    useEffect(() => {
        if (isDarkMode) {
            document.querySelector('body')?.classList.add('dark')
        } else {
            document.querySelector('body')?.classList.remove('dark')
        }

        localStorage.setItem('isDarkMode', isDarkMode ? 'enabled' : 'disabled')
    }, [isDarkMode])

    if (auth.isLoading) {
        return 'Loading...'
    }

    if (!auth.isAuthenticated) {
        return <Navigate to="/login" />
    }

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
            <Button type="button" onClick={handleOnLogout}>
                Logout
            </Button>
            <main className="flex justify-center mt-32">
                <Outlet />
            </main>
        </>
    )
}
