import { Toaster } from '@/components/ui/sonner'
import '@/index.css'
import AuthLayout from '@/layouts/AuthLayout'
import queryClient from '@/lib/queryClient'
import LoginPage from '@/pages/LoginPage'
import { QueryClientProvider } from '@tanstack/react-query'
import { StrictMode } from 'react'
import { BrowserRouter, Route, Routes } from 'react-router'

export default function App() {
    return (
        <StrictMode>
            <QueryClientProvider client={queryClient}>
                <BrowserRouter>
                    <Routes>
                        <Route element={<AuthLayout />}>
                            <Route path="/" element={<LoginPage />} />
                            <Route path="/login" element={<LoginPage />} />
                        </Route>
                    </Routes>
                </BrowserRouter>
                <Toaster />
            </QueryClientProvider>
        </StrictMode>
    )
}
