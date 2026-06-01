import api from '@/lib/api'
import { useMutation, useQuery } from '@tanstack/react-query'
import type { DevUser } from './types'

export const useDevUsers = (enabled = true) =>
    useQuery({ queryKey: ['dev-users'], queryFn: () => api.get<DevUser[]>('/dev/users'), enabled })

export const useLoginAs = () =>
    useMutation({ mutationFn: (email: string) => api.post('/dev/login-as', { email }) })
