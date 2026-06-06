import { Toaster } from '@/components/ui/sonner'
import DevLoginPanel from '@/features/dev/components/DevLoginPanel'
import { WorkspaceGuard } from '@/features/workspace/components/WorkspaceGuard'
import '@/index.css'
import AppLayout from '@/layouts/AppLayout'
import AuthLayout from '@/layouts/AuthLayout'
import queryClient from '@/lib/queryClient'
import InvitationsPage from '@/pages/InvitationsPage'
import LoginPage from '@/pages/LoginPage'
import WorkspaceMembersPage from '@/pages/WorkspaceMembersPage'
import WorkspaceProjectsPage from '@/pages/WorkspaceProjectsPage'
import WorkspaceProjectTasksPage from '@/pages/WorkspaceProjectTasksPage'
import { QueryClientProvider } from '@tanstack/react-query'
import { ThemeProvider } from 'next-themes'
import { StrictMode } from 'react'
import { BrowserRouter, Navigate, Route, Routes } from 'react-router'
import { AuthProvider } from './contexts/auth/AuthProvider'
import LandingPage from './pages/LandingPage'
import RegisterPage from './pages/RegisterPage'
import WorkspaceDashboardPage from './pages/WorkspaceDashboardPage'
import WorkspaceSettingsPage from './pages/WorkspaceSettingsPage'

export default function App() {
    return (
        <StrictMode>
            <QueryClientProvider client={queryClient}>
                <AuthProvider>
                    <ThemeProvider
                        attribute="class"
                        defaultTheme="system"
                        enableSystem={true}
                        disableTransitionOnChange
                    >
                        <BrowserRouter>
                            <Routes>
                                <Route element={<AuthLayout />}>
                                    <Route path="/login" element={<LoginPage />} />
                                    <Route path="/register" element={<RegisterPage />} />
                                </Route>
                                <Route element={<AppLayout />}>
                                    <Route path="/" element={<LandingPage />} />
                                    <Route
                                        path="/workspaces"
                                        element={<Navigate to="/" replace />}
                                    />
                                    <Route
                                        path="/workspaces/:id"
                                        element={<Navigate to="dashboard" replace />}
                                    />
                                    <Route element={<WorkspaceGuard />}>
                                        <Route
                                            path="/workspaces/:id/dashboard"
                                            element={<WorkspaceDashboardPage />}
                                        />
                                        <Route
                                            path="/workspaces/:id/settings"
                                            element={<WorkspaceSettingsPage />}
                                        />
                                        <Route
                                            path="/workspaces/:id/members"
                                            element={<WorkspaceMembersPage />}
                                        />
                                        <Route
                                            path="/workspaces/:id/projects"
                                            element={<WorkspaceProjectsPage />}
                                        />
                                        <Route
                                            path="/workspaces/:id/projects/:projectId/tasks"
                                            element={<WorkspaceProjectTasksPage />}
                                        />
                                    </Route>
                                    <Route path="/invitations" element={<InvitationsPage />} />
                                </Route>
                            </Routes>
                        </BrowserRouter>
                        <Toaster position="top-center" />
                        {import.meta.env.DEV && <DevLoginPanel />}
                    </ThemeProvider>
                </AuthProvider>
            </QueryClientProvider>
        </StrictMode>
    )
}
