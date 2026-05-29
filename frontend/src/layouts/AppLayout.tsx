import { ThemeToggle } from '@/components/ThemeToggle'
import { Button } from '@/components/ui/button'
import { AuthContext } from '@/contexts/auth/AuthContext'
import { useLogoutAction } from '@/features/auth/hooks/useLogoutAction'
import { WorkspaceSwitcher } from '@/features/workspace/components/WorkspaceSwitcher'
import { useWorkspaces } from '@/features/workspace/queries'
import { useContext } from 'react'
import { Navigate, Outlet } from 'react-router'

export default function AppLayout() {
    const auth = useContext(AuthContext)
    const { handleOnLogout } = useLogoutAction()
    const { data: workspaces } = useWorkspaces()

    if (auth.isLoading) {
        return 'Loading...'
    }

    if (!auth.isAuthenticated) {
        return <Navigate to="/login" />
    }

    return (
        <div className="flex h-screen">
            <aside className="flex w-60 flex-col gap-2 border-r p-4">
                {workspaces && workspaces.length > 0 && <WorkspaceSwitcher />}
                <div className="mt-auto flex flex-col gap-2">
                    <ThemeToggle />
                    <Button type="button" variant="outline" onClick={handleOnLogout}>
                        Logout
                    </Button>
                </div>
            </aside>
            <main className="flex-1 overflow-auto p-8">
                <Outlet />
            </main>
        </div>
    )
}
