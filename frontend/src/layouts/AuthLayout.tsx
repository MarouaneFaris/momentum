import { ThemeToggle } from '@/components/ThemeToggle'
import { AuthContext } from '@/contexts/auth/AuthContext'
import { useContext } from 'react'
import { Navigate, Outlet } from 'react-router'

export default function AuthLayout() {
    const auth = useContext(AuthContext)

    if (auth.isLoading) {
        return 'Loading...'
    }

    if (auth.isAuthenticated) {
        return <Navigate to="/" />
    }

    return (
        <>
            <ThemeToggle />
            <main className="flex justify-center mt-32">
                <Outlet />
            </main>
        </>
    )
}
