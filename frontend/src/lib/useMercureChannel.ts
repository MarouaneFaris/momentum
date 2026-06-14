import api from '@/lib/api'
import { useEffect, useRef } from 'react'

export type MercureChannelStatus = 'idle' | 'connecting' | 'open' | 'closed'

export type MercureChannelOptions<T> = {
    topic: string
    onMessage: (msg: T) => void
    enabled?: boolean
}

export function useMercureChannel<T>(opts: MercureChannelOptions<T>): {
    status: MercureChannelStatus
} {
    const { topic, enabled = true } = opts

    const onMessageRef = useRef(opts.onMessage)
    useEffect(() => {
        onMessageRef.current = opts.onMessage
    }, [opts.onMessage])

    useEffect(() => {
        if (!enabled) return

        const MERCURE_URL = import.meta.env.VITE_MERCURE_PUBLIC_URL as string | undefined
        if (!MERCURE_URL) throw new Error('[mercure] VITE_MERCURE_PUBLIC_URL is not defined')

        let cancelled = false
        let es: EventSource | undefined
        let refreshTimer: ReturnType<typeof setTimeout> | undefined

        const connect = async () => {
            if (cancelled) return

            const result = await api.get<{ expiresIn: number }>('/notifications/mercure-token')
            if (cancelled || !result) return

            es?.close()

            const url = new URL(MERCURE_URL, window.location.origin)
            url.searchParams.append('topic', topic)
            es = new EventSource(url.toString(), { withCredentials: true })

            es.onmessage = (event: MessageEvent) => {
                try {
                    onMessageRef.current(JSON.parse(event.data as string) as T)
                } catch {
                    // ignore malformed messages
                }
            }

            const refreshIn = (result.expiresIn - 30) * 1000
            if (refreshIn > 0) {
                refreshTimer = setTimeout(() => void connect(), refreshIn)
            }
        }

        void connect()

        return () => {
            cancelled = true
            clearTimeout(refreshTimer)
            es?.close()
        }
    }, [enabled, topic])

    return { status: 'idle' }
}
