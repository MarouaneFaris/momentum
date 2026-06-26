import { MomentumLogo } from '@/components/MomentumLogo'
import { ThemeToggle } from '@/components/ThemeToggle'
import { ThemeToggleSwitch } from '@/components/ThemeToggleSwitch'
import { Badge } from '@/components/ui/badge'
import { UserMenu } from '@/components/UserMenu'
import { AuthContext } from '@/contexts/auth/AuthContext'
import { useMyInvitations } from '@/features/membership/queries'
import { NotificationBell } from '@/features/notification/components/NotificationBell'
import { useNotificationStream } from '@/features/notification/hooks/useNotificationStream'
import { projectColorValue } from '@/features/project/projectColor'
import { useProjects } from '@/features/project/queries'
import { MobileWorkspaceSwitcher } from '@/features/workspace/components/MobileWorkspaceSwitcher'
import { WorkspaceSwitcher } from '@/features/workspace/components/WorkspaceSwitcher'
import { useActiveWorkspaceId } from '@/features/workspace/hooks/useActiveWorkspaceId'
import { useWorkspace } from '@/features/workspace/queries'
import { FolderOpen, Home, LayoutDashboard, ListTodo, Mail, Settings, Users } from 'lucide-react'
import { useContext } from 'react'
import { Link, NavLink, Navigate, Outlet } from 'react-router'

export default function AppLayout() {
    const auth = useContext(AuthContext)
    const workspaceId = useActiveWorkspaceId()
    const { data: invitations } = useMyInvitations()
    const pendingCount = invitations?.length ?? 0
    const { data: workspace } = useWorkspace(workspaceId ?? '')
    const isOwner = workspace?.role === 'owner'
    const { data: projects } = useProjects(workspaceId ?? '')
    const sortedProjects = [...(projects ?? [])].sort(
        (a, b) => new Date(a.createdAt).getTime() - new Date(b.createdAt).getTime(),
    )
    useNotificationStream()

    if (auth.isLoading) return null
    if (!auth.isAuthenticated) return <Navigate to="/login" />

    return (
        <div className="flex h-screen flex-col">
            {/* Desktop topbar */}
            <header className="bg-sidebar hidden h-12 shrink-0 items-center gap-3 border-b px-4 md:flex">
                <Link to="/" className="flex items-center gap-3">
                    <MomentumLogo size="sm" />
                    <span className="text-sm font-semibold tracking-tight">
                        <span className="text-primary">m</span>omentum
                    </span>
                </Link>
                <span className="bg-border mx-1 h-5 w-px" />
                <WorkspaceSwitcher />
                <span className="flex-1" />
                <ThemeToggle />
                <NotificationBell />
                <UserMenu />
            </header>
            {/* Mobile topbar */}
            <header className="bg-sidebar flex h-12 shrink-0 items-center gap-2 border-b px-3 md:hidden">
                {workspaceId ? (
                    <MobileWorkspaceSwitcher />
                ) : (
                    <Link to="/" className="flex items-center gap-3">
                        <MomentumLogo size="sm" />
                        <span className="text-sm font-semibold tracking-tight">
                            <span className="text-primary">m</span>omentum
                        </span>
                    </Link>
                )}
                <span className="flex-1" />
                <ThemeToggleSwitch />
                <NotificationBell />
                <UserMenu />
            </header>
            <div className="flex flex-1 overflow-hidden">
                {/* Sidebar — desktop only */}
                <aside className="bg-sidebar hidden w-[200px] shrink-0 flex-col overflow-y-auto border-r px-2 py-3 md:flex">
                    <p className="text-muted-foreground px-2 py-1 text-[10px] font-medium tracking-[0.08em] uppercase">
                        Main
                    </p>
                    {!workspaceId && (
                        <NavLink
                            to="/"
                            end
                            className={({ isActive }) =>
                                `flex items-center gap-2 rounded-md px-2 py-1.5 text-sm ${
                                    isActive
                                        ? 'bg-primary/10 text-primary font-medium'
                                        : 'text-muted-foreground hover:bg-muted hover:text-foreground'
                                }`
                            }
                        >
                            <Home className="h-4 w-4 shrink-0" />
                            Home
                        </NavLink>
                    )}
                    {workspaceId && (
                        <>
                            <NavLink
                                to={`/workspaces/${workspaceId}/dashboard`}
                                className={({ isActive }) =>
                                    `flex items-center gap-2 rounded-md px-2 py-1.5 text-sm ${
                                        isActive
                                            ? 'bg-primary/10 text-primary font-medium'
                                            : 'text-muted-foreground hover:bg-muted hover:text-foreground'
                                    }`
                                }
                            >
                                <LayoutDashboard className="h-4 w-4 shrink-0" />
                                Dashboard
                            </NavLink>
                            <NavLink
                                to={`/workspaces/${workspaceId}/my-tasks`}
                                className={({ isActive }) =>
                                    `flex items-center gap-2 rounded-md px-2 py-1.5 text-sm ${
                                        isActive
                                            ? 'bg-primary/10 text-primary font-medium'
                                            : 'text-muted-foreground hover:bg-muted hover:text-foreground'
                                    }`
                                }
                            >
                                <ListTodo className="h-4 w-4 shrink-0" />
                                My tasks
                            </NavLink>
                        </>
                    )}
                    <NavLink
                        to="/invitations"
                        className={({ isActive }) =>
                            `flex items-center gap-2 rounded-md px-2 py-1.5 text-sm ${
                                isActive
                                    ? 'bg-primary/10 text-primary font-medium'
                                    : 'text-muted-foreground hover:bg-muted hover:text-foreground'
                            }`
                        }
                    >
                        <Mail className="h-4 w-4 shrink-0" />
                        Invitations
                        {pendingCount > 0 && (
                            <Badge className="ml-auto h-4 min-w-4 px-2 text-[10px]">
                                {pendingCount}
                            </Badge>
                        )}
                    </NavLink>
                    {workspaceId && (
                        <>
                            <p className="text-muted-foreground mt-3 px-2 py-1 text-[10px] font-medium tracking-[0.08em] uppercase">
                                Projects
                            </p>
                            <NavLink
                                end
                                to={`/workspaces/${workspaceId}/projects`}
                                className={({ isActive }) =>
                                    `flex items-center gap-2 rounded-md px-2 py-1.5 text-sm ${
                                        isActive
                                            ? 'bg-primary/10 text-primary font-medium'
                                            : 'text-muted-foreground hover:bg-muted hover:text-foreground'
                                    }`
                                }
                            >
                                <FolderOpen className="h-4 w-4 shrink-0" />
                                All projects
                            </NavLink>
                            {sortedProjects.map((project) => (
                                <NavLink
                                    key={project.id}
                                    to={`/workspaces/${workspaceId}/projects/${project.id}/tasks`}
                                    className={({ isActive }) =>
                                        `flex items-center gap-2 rounded-md px-2 py-1.5 text-xs ${
                                            isActive
                                                ? 'bg-primary/10 text-primary font-medium'
                                                : 'text-muted-foreground hover:bg-muted hover:text-foreground'
                                        }`
                                    }
                                >
                                    <span
                                        className="h-1.5 w-1.5 shrink-0 rounded-full"
                                        style={{ background: projectColorValue(project.color) }}
                                    />
                                    <span className="truncate">{project.name}</span>
                                </NavLink>
                            ))}
                        </>
                    )}
                    <div className="mt-auto">
                        {workspaceId && (
                            <>
                                <p className="text-muted-foreground px-2 pt-2 pb-1 text-[10px] font-medium tracking-[0.08em] uppercase">
                                    Workspace
                                </p>
                                <NavLink
                                    to={`/workspaces/${workspaceId}/members`}
                                    className={({ isActive }) =>
                                        `flex items-center gap-2 rounded-md px-2 py-1.5 text-sm ${
                                            isActive
                                                ? 'bg-primary/10 text-primary font-medium'
                                                : 'text-muted-foreground hover:bg-muted hover:text-foreground'
                                        }`
                                    }
                                >
                                    <Users className="h-4 w-4 shrink-0" />
                                    Members
                                </NavLink>
                                {isOwner && (
                                    <NavLink
                                        to={`/workspaces/${workspaceId}/settings`}
                                        className={({ isActive }) =>
                                            `flex items-center gap-2 rounded-md px-2 py-1.5 text-sm ${
                                                isActive
                                                    ? 'bg-primary/10 text-primary font-medium'
                                                    : 'text-muted-foreground hover:bg-muted hover:text-foreground'
                                            }`
                                        }
                                    >
                                        <Settings className="h-4 w-4 shrink-0" />
                                        Settings
                                    </NavLink>
                                )}
                            </>
                        )}
                        <p className="text-muted-foreground/60 px-2 pt-3 pb-1 text-[10px]">
                            {import.meta.env.VITE_APP_VERSION ?? 'dev'}
                        </p>
                    </div>
                </aside>
                <main className="bg-background flex-1 overflow-auto pb-14 md:pb-0">
                    <Outlet />
                </main>
            </div>
            {/* Bottom tab bar — mobile only */}
            {!workspaceId && (
                <nav className="bg-card fixed right-0 bottom-0 left-0 z-50 flex h-14 shrink-0 items-center justify-around border-t px-2 pb-1 md:hidden">
                    <NavLink
                        to="/"
                        end
                        className={({ isActive }) =>
                            `flex flex-1 flex-col items-center gap-0.5 ${
                                isActive ? 'text-primary' : 'text-muted-foreground'
                            }`
                        }
                    >
                        {({ isActive }) => (
                            <>
                                <Home
                                    className={`h-5 w-5 ${isActive ? 'text-primary' : 'text-muted-foreground'}`}
                                />
                                <span
                                    className={`text-[9px] font-medium ${isActive ? 'text-primary' : 'text-muted-foreground'}`}
                                >
                                    Home
                                </span>
                            </>
                        )}
                    </NavLink>
                    <NavLink
                        to="/invitations"
                        className={({ isActive }) =>
                            `flex flex-1 flex-col items-center gap-0.5 ${
                                isActive ? 'text-primary' : 'text-muted-foreground'
                            }`
                        }
                    >
                        {({ isActive }) => (
                            <>
                                <Mail
                                    className={`h-5 w-5 ${isActive ? 'text-primary' : 'text-muted-foreground'}`}
                                />
                                <span
                                    className={`text-[9px] font-medium ${isActive ? 'text-primary' : 'text-muted-foreground'}`}
                                >
                                    Invitations
                                </span>
                                {pendingCount > 0 && (
                                    <Badge className="absolute top-1 ml-6 h-4 min-w-4 px-1 text-[9px]">
                                        {pendingCount}
                                    </Badge>
                                )}
                            </>
                        )}
                    </NavLink>
                </nav>
            )}
            {workspaceId && (
                <nav className="bg-card fixed right-0 bottom-0 left-0 z-50 flex h-14 shrink-0 items-center justify-around border-t px-2 pb-1 md:hidden">
                    <NavLink
                        to={`/workspaces/${workspaceId}/dashboard`}
                        className={({ isActive }) =>
                            `flex flex-1 flex-col items-center gap-0.5 ${
                                isActive ? 'text-primary' : 'text-muted-foreground'
                            }`
                        }
                    >
                        {({ isActive }) => (
                            <>
                                <LayoutDashboard
                                    className={`h-5 w-5 ${isActive ? 'text-primary' : 'text-muted-foreground'}`}
                                />
                                <span
                                    className={`text-[9px] font-medium ${isActive ? 'text-primary' : 'text-muted-foreground'}`}
                                >
                                    Home
                                </span>
                            </>
                        )}
                    </NavLink>
                    <NavLink
                        to={`/workspaces/${workspaceId}/my-tasks`}
                        className={({ isActive }) =>
                            `flex flex-1 flex-col items-center gap-0.5 ${
                                isActive ? 'text-primary' : 'text-muted-foreground'
                            }`
                        }
                    >
                        {({ isActive }) => (
                            <>
                                <ListTodo
                                    className={`h-5 w-5 ${isActive ? 'text-primary' : 'text-muted-foreground'}`}
                                />
                                <span
                                    className={`text-[9px] font-medium ${isActive ? 'text-primary' : 'text-muted-foreground'}`}
                                >
                                    My tasks
                                </span>
                            </>
                        )}
                    </NavLink>
                    <NavLink
                        to={`/workspaces/${workspaceId}/projects`}
                        className={({ isActive }) =>
                            `flex flex-1 flex-col items-center gap-0.5 ${
                                isActive ? 'text-primary' : 'text-muted-foreground'
                            }`
                        }
                    >
                        {({ isActive }) => (
                            <>
                                <FolderOpen
                                    className={`h-5 w-5 ${isActive ? 'text-primary' : 'text-muted-foreground'}`}
                                />
                                <span
                                    className={`text-[9px] font-medium ${isActive ? 'text-primary' : 'text-muted-foreground'}`}
                                >
                                    Projects
                                </span>
                            </>
                        )}
                    </NavLink>
                    <NavLink
                        to={`/workspaces/${workspaceId}/settings`}
                        className={({ isActive }) =>
                            `flex flex-1 flex-col items-center gap-0.5 ${
                                isActive ? 'text-primary' : 'text-muted-foreground'
                            }`
                        }
                    >
                        {({ isActive }) => (
                            <>
                                <Settings
                                    className={`h-5 w-5 ${isActive ? 'text-primary' : 'text-muted-foreground'}`}
                                />
                                <span
                                    className={`text-[9px] font-medium ${isActive ? 'text-primary' : 'text-muted-foreground'}`}
                                >
                                    Settings
                                </span>
                            </>
                        )}
                    </NavLink>
                </nav>
            )}
        </div>
    )
}
