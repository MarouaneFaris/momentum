import api from '@/lib/api'
import { useMutation, useQueryClient } from '@tanstack/react-query'
import { NOTIFICATIONS_QUERY_KEY } from '../queries'
import type { Notification } from '../types'

export const useMarkNotificationRead = () => {
    const queryClient = useQueryClient()
    return useMutation({
        mutationFn: (id: string) => api.patch(`/notifications/${id}/read`),
        onMutate: (id) => {
            const readAt = new Date().toISOString()
            queryClient.setQueryData<Notification[]>(NOTIFICATIONS_QUERY_KEY, (prev) =>
                prev?.map((n) => (n.id === id ? { ...n, readAt } : n)),
            )
        },
    })
}

export const useDeleteNotification = () => {
    const queryClient = useQueryClient()
    return useMutation({
        mutationFn: (id: string) => api.delete(`/notifications/${id}`),
        onMutate: (id) => {
            queryClient.setQueryData<Notification[]>(NOTIFICATIONS_QUERY_KEY, (prev) =>
                prev?.filter((n) => n.id !== id),
            )
        },
    })
}
