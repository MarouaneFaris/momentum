import { useQueryClient } from '@tanstack/react-query'
import {
    NOTIFICATIONS_QUERY_KEY,
    useDeleteNotification,
    useMarkAllNotificationsRead,
    useMarkNotificationRead,
} from '../queries'
import type { Notification } from '../types'

export const useNotificationActions = () => {
    const queryClient = useQueryClient()
    const { mutate: markRead } = useMarkNotificationRead()
    const { mutate: deleteNotif } = useDeleteNotification()
    const { mutate: markAllRead } = useMarkAllNotificationsRead()

    const handleMarkRead = (id: string) => {
        const snapshot = queryClient.getQueryData<Notification[]>(NOTIFICATIONS_QUERY_KEY)
        const readAt = new Date().toISOString()
        queryClient.setQueryData<Notification[]>(NOTIFICATIONS_QUERY_KEY, (prev) =>
            prev?.map((n) => (n.id === id ? { ...n, readAt } : n)),
        )
        markRead(id, {
            onError: () => queryClient.setQueryData(NOTIFICATIONS_QUERY_KEY, snapshot),
        })
    }

    const handleDelete = (id: string) => {
        const snapshot = queryClient.getQueryData<Notification[]>(NOTIFICATIONS_QUERY_KEY)
        queryClient.setQueryData<Notification[]>(NOTIFICATIONS_QUERY_KEY, (prev) =>
            prev?.filter((n) => n.id !== id),
        )
        deleteNotif(id, {
            onError: () => queryClient.setQueryData(NOTIFICATIONS_QUERY_KEY, snapshot),
        })
    }

    const handleMarkAllRead = () => {
        const snapshot = queryClient.getQueryData<Notification[]>(NOTIFICATIONS_QUERY_KEY)
        const readAt = new Date().toISOString()
        queryClient.setQueryData<Notification[]>(NOTIFICATIONS_QUERY_KEY, (prev) =>
            prev?.map((n) => (n.readAt === null ? { ...n, readAt } : n)),
        )
        markAllRead(undefined, {
            onError: () => queryClient.setQueryData(NOTIFICATIONS_QUERY_KEY, snapshot),
        })
    }

    return { handleMarkRead, handleDelete, handleMarkAllRead }
}
