import api from '@/lib/api'
import { ROUTES } from '@/lib/routes'
import { useMutation, useQuery } from '@tanstack/react-query'
import type {
    AuthResponse,
    LoginPayload,
    LoginResponse,
    RegisterPayload,
    RegisterResponse,
    ResendVerificationPayload,
    VerifyEmailPayload,
    VerifyEmailResponse,
} from './types'

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

export const useRegister = () =>
    useMutation({
        mutationFn: (data: RegisterPayload) => api.post<RegisterResponse>(ROUTES.register, data),
    })

export const useVerifyEmail = () =>
    useMutation({
        mutationFn: (data: VerifyEmailPayload) =>
            api.post<VerifyEmailResponse>(ROUTES.verifyEmail, data),
    })

export const useResendVerification = () =>
    useMutation({
        mutationFn: (data: ResendVerificationPayload) =>
            api.post<{ message: string }>(ROUTES.resendVerification, data),
    })
