import api from '@/lib/api'
import { ROUTES } from '@/lib/routes'
import { useMutation, useQuery } from '@tanstack/react-query'
import type { AuthResponse, LoginPayload, LoginResponse } from './types'

export const useLogin = () =>
    useMutation({
        mutationFn: (data: LoginPayload) => api.post<LoginResponse>(ROUTES.login, data),
    })

export const useLogout = () =>
    useMutation({
        mutationFn: () => api.post<null>(ROUTES.logout),
    })

export const useAuth = () =>
    useQuery({ queryKey: ['me'], queryFn: () => api.get<AuthResponse>(ROUTES.me), retry: false })
