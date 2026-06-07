import { AuthContext } from '@/contexts/auth/AuthContext'
import { useQueryClient } from '@tanstack/react-query'
import { useContext, useEffect } from 'react'
import { NOTIFICATIONS_QUERY_KEY, useNotificationList } from '../queries'

const getMercureUrl = () => {
    const apiUrl = import.meta.env.VITE_API_URL as string
    return apiUrl.replace(/\/api$/, '/.well-known/mercure')
}

export const useNotifications = () => {
    const { user, isAuthenticated } = useContext(AuthContext)
    const queryClient = useQueryClient()
    const query = useNotificationList()

    useEffect(() => {
        if (!isAuthenticated || !user?.id) return

        const url = new URL(getMercureUrl())
        url.searchParams.append('topic', `/notifications/${user.id}`)

        const es = new EventSource(url.toString())

        es.onmessage = () => {
            void queryClient.invalidateQueries({ queryKey: NOTIFICATIONS_QUERY_KEY })
        }

        return () => {
            es.close()
        }
    }, [isAuthenticated, user?.id, queryClient])

    return query
}
