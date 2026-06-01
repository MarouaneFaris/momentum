import { MomentumLogo } from '@/components/MomentumLogo'
import { ThemeToggle } from '@/components/ThemeToggle'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { UserMenu } from '@/components/UserMenu'
import { AuthContext } from '@/contexts/auth/AuthContext'
import { useMyInvitations } from '@/features/membership/queries'
import { WorkspaceSwitcher } from '@/features/workspace/components/WorkspaceSwitcher'
import { useActiveWorkspaceId } from '@/features/workspace/hooks/useActiveWorkspaceId'
import { Bell, LayoutDashboard, Mail, Settings, Users } from 'lucide-react'
import { useContext } from 'react'
import { NavLink, Navigate, Outlet } from 'react-router'

export default function AppLayout() {
    const auth = useContext(AuthContext)
    const workspaceId = useActiveWorkspaceId()
    const { data: invitations } = useMyInvitations()
    const pendingCount = invitations?.length ?? 0

    if (auth.isLoading) return null
    if (!auth.isAuthenticated) return <Navigate to="/login" />

    return (
        <div className="flex h-screen flex-col">
            <header className="flex h-12 flex-shrink-0 items-center gap-3 border-b bg-sidebar px-4">
                <MomentumLogo size="sm" />
                <span className="text-sm font-semibold tracking-tight">
                    <span className="text-primary">m</span>omentum
                </span>
                <span className="mx-1 h-5 w-px bg-border" />
                <WorkspaceSwitcher />
                <span className="flex-1" />
                <ThemeToggle />
                <Button variant="ghost" size="icon" aria-label="Notifications">
                    <Bell className="h-4 w-4" />
                </Button>
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
                            <LayoutDashboard className="h-4 w-4 flex-shrink-0" />
                            Dashboard
                        </NavLink>
                    )}
                    <NavLink
                        to="/invitations"
                        className={({ isActive }) =>
                            `flex items-center gap-2 rounded-md px-2 py-1.5 text-sm ${
                                isActive
                                    ? 'bg-primary/10 font-medium text-primary'
                                    : 'text-muted-foreground hover:bg-muted hover:text-foreground'
                            }`
                        }
                    >
                        <Mail className="h-4 w-4 flex-shrink-0" />
                        Invitations
                        {pendingCount > 0 && (
                            <Badge className="ml-auto h-4 min-w-4 px-2 text-[10px]">
                                {pendingCount}
                            </Badge>
                        )}
                    </NavLink>
                    {workspaceId && (
                        <div className="mt-auto">
                            <p className="px-2 pb-1 pt-2 text-[10px] font-medium uppercase tracking-[0.08em] text-muted-foreground">
                                Workspace
                            </p>
                            <NavLink
                                to={`/workspaces/${workspaceId}/members`}
                                className={({ isActive }) =>
                                    `flex items-center gap-2 rounded-md px-2 py-1.5 text-sm ${
                                        isActive
                                            ? 'bg-primary/10 font-medium text-primary'
                                            : 'text-muted-foreground hover:bg-muted hover:text-foreground'
                                    }`
                                }
                            >
                                <Users className="h-4 w-4 flex-shrink-0" />
                                Members
                            </NavLink>
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
                                <Settings className="h-4 w-4 flex-shrink-0" />
                                Settings
                            </NavLink>
                        </div>
                    )}
                </aside>
                <main className="flex-1 overflow-auto bg-background p-8">
                    <Outlet />
                </main>
            </div>
        </div>
    )
}
