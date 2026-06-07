export type MercureTopic = `/notifications/${string}`

export const subscribe = <T>(
    topic: MercureTopic,
    onMessage: (data: T) => void,
    opts?: { onError?: (e: Event) => void; token?: string },
): (() => void) => {
    const MERCURE_URL = import.meta.env.VITE_MERCURE_PUBLIC_URL as string | undefined

    if (!MERCURE_URL) {
        throw new Error('[mercure] VITE_MERCURE_PUBLIC_URL is not defined')
    }

    const url = new URL(MERCURE_URL)
    url.searchParams.append('topic', topic)

    if (opts?.token) {
        url.searchParams.append('authorization', opts.token)
    }

    const handleError = opts?.onError ?? ((e: Event) => console.warn('[mercure] error', e))

    const es = new EventSource(url.toString(), { withCredentials: true })

    es.onmessage = (event: MessageEvent) => {
        try {
            onMessage(JSON.parse(event.data as string) as T)
        } catch {
            handleError(event)
        }
    }

    es.onerror = handleError

    return () => es.close()
}
