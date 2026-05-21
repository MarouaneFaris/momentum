import api from '@/lib/api'
import { ROUTES } from '@/lib/routes'
import { useMutation } from '@tanstack/react-query'
import type { LoginPayload, LoginResponse } from './types'

export const useLogin = () =>
    useMutation({
        mutationFn: (data: LoginPayload) => api.post<LoginResponse>(ROUTES.login, data),
    })
