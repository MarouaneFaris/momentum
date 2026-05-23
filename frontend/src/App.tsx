import { Toaster } from '@/components/ui/sonner'
import '@/index.css'
import AppLayout from '@/layouts/AppLayout'
import AuthLayout from '@/layouts/AuthLayout'
import queryClient from '@/lib/queryClient'
import LoginPage from '@/pages/LoginPage'
import { QueryClientProvider } from '@tanstack/react-query'
import { ThemeProvider } from 'next-themes'
import { StrictMode } from 'react'
import { BrowserRouter, Route, Routes } from 'react-router'
import { AuthProvider } from './contexts/auth/AuthProvider'
import DashBoardPage from './pages/DashboardPage'
import RegisterPage from './pages/RegisterPage'

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
                                    <Route path="/" element={<DashBoardPage />} />
                                </Route>
                            </Routes>
                        </BrowserRouter>
                        <Toaster position="top-center" />
                    </ThemeProvider>
                </AuthProvider>
            </QueryClientProvider>
        </StrictMode>
    )
}
