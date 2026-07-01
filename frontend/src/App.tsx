import { Toaster } from '@/components/ui/sonner'
import { WorkspaceGuard } from '@/features/workspace/components/WorkspaceGuard'
import '@/index.css'
import AppLayout from '@/layouts/AppLayout'
import AuthLayout from '@/layouts/AuthLayout'
import queryClient from '@/lib/queryClient'
import { QueryClientProvider } from '@tanstack/react-query'
import { ThemeProvider } from 'next-themes'
import { lazy, StrictMode, Suspense } from 'react'
import { BrowserRouter, Navigate, Route, Routes } from 'react-router'
import { AuthProvider } from './contexts/auth/AuthProvider'

const DevSpeedDial = lazy(() => import('@/features/dev/components/DevSpeedDial'))
const InvitationsPage = lazy(() => import('@/pages/InvitationsPage'))
const LoginPage = lazy(() => import('@/pages/LoginPage'))
const VerifyEmailPage = lazy(() => import('@/pages/VerifyEmailPage'))
const WorkspaceMembersPage = lazy(() => import('@/pages/WorkspaceMembersPage'))
const WorkspaceMyTasksPage = lazy(() => import('@/pages/WorkspaceMyTasksPage'))
const WorkspaceProjectsPage = lazy(() => import('@/pages/WorkspaceProjectsPage'))
const WorkspaceProjectTasksPage = lazy(() => import('@/pages/WorkspaceProjectTasksPage'))
const LandingPage = lazy(() => import('./pages/LandingPage'))
const RegisterPage = lazy(() => import('./pages/RegisterPage'))
const WorkspaceDashboardPage = lazy(() => import('./pages/WorkspaceDashboardPage'))
const WorkspaceSettingsPage = lazy(() => import('./pages/WorkspaceSettingsPage'))

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
                            <Suspense>
                                <Routes>
                                    <Route element={<AuthLayout />}>
                                        <Route path="/login" element={<LoginPage />} />
                                        <Route path="/register" element={<RegisterPage />} />
                                        <Route path="/verify-email" element={<VerifyEmailPage />} />
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
                                                path="/workspaces/:id/my-tasks"
                                                element={<WorkspaceMyTasksPage />}
                                            />
                                            <Route
                                                path="/workspaces/:id/projects/:projectId/tasks"
                                                element={<WorkspaceProjectTasksPage />}
                                            />
                                        </Route>
                                        <Route path="/invitations" element={<InvitationsPage />} />
                                    </Route>
                                </Routes>
                            </Suspense>
                        </BrowserRouter>
                        <Toaster position="top-center" />
                        {import.meta.env.DEV && (
                            <Suspense>
                                <DevSpeedDial />
                            </Suspense>
                        )}
                    </ThemeProvider>
                </AuthProvider>
            </QueryClientProvider>
        </StrictMode>
    )
}
