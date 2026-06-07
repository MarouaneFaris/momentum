import api from '@/lib/api'
import { useSettledQuery } from '@/lib/useSettledQuery'
import { useMutation, useQueryClient } from '@tanstack/react-query'
import type { Notification } from './types'

export const NOTIFICATIONS_QUERY_KEY = ['notifications'] as const

export const useNotificationList = () =>
    useSettledQuery({
        queryKey: NOTIFICATIONS_QUERY_KEY,
        queryFn: () => api.get<Notification[]>('/notifications'),
    })

export const useMarkAllNotificationsRead = () => {
    const queryClient = useQueryClient()
    return useMutation({
        mutationFn: () => api.patch('/notifications/read-all'),
        onSuccess: () => {
            const readAt = new Date().toISOString()
            queryClient.setQueryData<Notification[]>(NOTIFICATIONS_QUERY_KEY, (prev) =>
                prev?.map((n) => (n.read_at === null ? { ...n, read_at: readAt } : n)),
            )
        },
    })
}
