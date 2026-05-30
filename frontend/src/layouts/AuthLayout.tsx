import { MomentumLogo } from '@/components/MomentumLogo'
import { AuthContext } from '@/contexts/auth/AuthContext'
import { useContext } from 'react'
import { Navigate, Outlet, useLocation } from 'react-router'

const LOGIN_BULLETS = [
    'Manage tasks across projects and teams',
    'Real-time notifications for your workspace',
    'Invite members, assign roles, stay in sync',
]

const REGISTER_BULLETS = [
    'Free to start, no credit card required',
    'Create your workspace in seconds',
    'Invite your team right away',
]

export default function AuthLayout() {
    const auth = useContext(AuthContext)
    const { pathname } = useLocation()

    if (auth.isLoading) return null
    if (auth.isAuthenticated) return <Navigate to="/" />

    const bullets = pathname === '/register' ? REGISTER_BULLETS : LOGIN_BULLETS

    return (
        <div className="grid min-h-screen grid-cols-[1fr_480px]">
            <div className="flex flex-col items-center justify-center gap-3 border-r bg-muted p-12">
                <MomentumLogo size="lg" />
                <div className="flex w-full max-w-[280px] items-center gap-3">
                    <span className="h-px flex-1 bg-border" />
                    <span className="whitespace-nowrap text-[10px] font-medium uppercase tracking-[0.18em] text-muted-foreground">
                        Task · Project · Team
                    </span>
                    <span className="h-px flex-1 bg-border" />
                </div>
                <ul className="mt-2 flex w-full max-w-[260px] flex-col gap-2">
                    {bullets.map((text) => (
                        <li key={text} className="flex items-center gap-2">
                            <span className="h-1 w-1 flex-shrink-0 rounded-full bg-primary" />
                            <span className="text-xs text-muted-foreground">{text}</span>
                        </li>
                    ))}
                </ul>
            </div>
            <div className="flex flex-col justify-center bg-card p-12">
                <Outlet />
            </div>
        </div>
    )
}
