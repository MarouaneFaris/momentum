import api from '@/lib/api'
import { useSettledQuery } from '@/lib/useSettledQuery'
import { useMutation } from '@tanstack/react-query'
import type { Notification } from './types'

export const NOTIFICATIONS_QUERY_KEY = ['notifications'] as const

export const useNotificationList = () =>
    useSettledQuery({
        queryKey: NOTIFICATIONS_QUERY_KEY,
        queryFn: () => api.get<Notification[]>('/notifications'),
    })

export const useMarkNotificationRead = () =>
    useMutation({
        mutationFn: (id: string) => api.patch(`/notifications/${id}/read`),
    })

export const useDeleteNotification = () =>
    useMutation({
        mutationFn: (id: string) => api.delete(`/notifications/${id}`),
    })

export const useMarkAllNotificationsRead = () =>
    useMutation({
        mutationFn: () => api.patch('/notifications/read-all'),
    })
