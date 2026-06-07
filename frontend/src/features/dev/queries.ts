import api from '@/lib/api'
import { useSettledQuery } from '@/lib/useSettledQuery'
import { useMutation } from '@tanstack/react-query'
import type { NotificationType } from '@/features/notification/types'
import type { DevUser } from './types'

export const useDevUsers = () =>
    useSettledQuery({
        queryKey: ['dev-users'],
        queryFn: () => api.get<DevUser[]>('/dev/users'),
        requireAuth: false,
        enabled: import.meta.env.DEV,
    })

export const useLoginAs = () =>
    useMutation({ mutationFn: (email: string) => api.post('/dev/login-as', { email }) })

export const useTriggerNotification = () =>
    useMutation({
        mutationFn: (type: NotificationType) => api.post('/dev/notifications/trigger', { type }),
    })
