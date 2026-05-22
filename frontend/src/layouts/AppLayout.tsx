import { ThemeToggle } from '@/components/ThemeToggle'
import { Button } from '@/components/ui/button'
import { AuthContext } from '@/contexts/auth/AuthContext'
import { useLogoutAction } from '@/features/auth/hooks/useLogoutAction'
import { useContext } from 'react'
import { Navigate, Outlet } from 'react-router'

export default function AppLayout() {
    const auth = useContext(AuthContext)
    const { handleOnLogout } = useLogoutAction()

    if (auth.isLoading) {
        return 'Loading...'
    }

    if (!auth.isAuthenticated) {
        return <Navigate to="/login" />
    }

    return (
        <>
            <ThemeToggle />
            <Button type="button" onClick={handleOnLogout}>
                Logout
            </Button>
            <main className="flex justify-center mt-32">
                <Outlet />
            </main>
        </>
    )
}
