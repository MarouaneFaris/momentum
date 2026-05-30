import { MomentumLogo } from '@/components/MomentumLogo'
import { ThemeToggle } from '@/components/ThemeToggle'
import { UserMenu } from '@/components/UserMenu'
import { AuthContext } from '@/contexts/auth/AuthContext'
import { WorkspaceSwitcher } from '@/features/workspace/components/WorkspaceSwitcher'
import { Bell, LayoutDashboard, Settings } from 'lucide-react'
import { useContext } from 'react'
import { NavLink, Navigate, Outlet, useParams } from 'react-router'

export default function AppLayout() {
    const auth = useContext(AuthContext)
    const { id: workspaceId } = useParams<{ id: string }>()

    if (auth.isLoading) return null
    if (!auth.isAuthenticated) return <Navigate to="/login" />

    return (
        <div className="flex h-screen flex-col">
            <header className="flex h-12 flex-shrink-0 items-center gap-3 border-b bg-sidebar px-4">
                <MomentumLogo size="sm" />
                <span className="mx-1 h-5 w-px bg-border" />
                <WorkspaceSwitcher />
                <span className="flex-1" />
                <ThemeToggle />
                <button
                    className="flex h-8 w-8 items-center justify-center rounded-md text-muted-foreground hover:bg-muted hover:text-foreground"
                    aria-label="Notifications"
                >
                    <Bell className="h-4 w-4" />
                </button>
                <UserMenu />
            </header>
            <div className="flex flex-1 overflow-hidden">
                <aside className="flex w-[200px] flex-shrink-0 flex-col overflow-y-auto border-r bg-sidebar px-2 py-3">
                    <p className="px-2 pb-1 pt-1 text-[10px] font-medium uppercase tracking-[0.08em] text-muted-foreground">
                        Main
                    </p>
                    {workspaceId && (
                        <NavLink
                            to={`/workspaces/${workspaceId}/dashboard`}
                            className={({ isActive }) =>
                                `flex items-center gap-2 rounded-md px-2 py-1.5 text-sm ${
                                    isActive
                                        ? 'bg-primary/10 font-medium text-primary'
                                        : 'text-muted-foreground hover:bg-muted hover:text-foreground'
                                }`
                            }
                        >
                            <LayoutDashboard className="h-[15px] w-[15px] flex-shrink-0" />
                            Dashboard
                        </NavLink>
                    )}
                    <p className="px-2 pb-1 pt-2 text-[10px] font-medium uppercase tracking-[0.08em] text-muted-foreground">
                        Workspace
                    </p>
                    {workspaceId && (
                        <NavLink
                            to={`/workspaces/${workspaceId}/settings`}
                            className={({ isActive }) =>
                                `flex items-center gap-2 rounded-md px-2 py-1.5 text-sm ${
                                    isActive
                                        ? 'bg-primary/10 font-medium text-primary'
                                        : 'text-muted-foreground hover:bg-muted hover:text-foreground'
                                }`
                            }
                        >
                            <Settings className="h-[15px] w-[15px] flex-shrink-0" />
                            Settings
                        </NavLink>
                    )}
                </aside>
                <main className="flex-1 overflow-auto bg-background p-8">
                    <Outlet />
                </main>
            </div>
        </div>
    )
}
