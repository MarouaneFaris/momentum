import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'

class MockEventSource {
    static instances: MockEventSource[] = []
    url: string
    withCredentials: boolean
    onmessage: ((e: MessageEvent) => void) | null = null
    onerror: ((e: Event) => void) | null = null
    close = vi.fn()

    constructor(url: string, init?: EventSourceInit) {
        this.url = url
        this.withCredentials = init?.withCredentials ?? false
        MockEventSource.instances.push(this)
    }

    emit(data: unknown) {
        this.onmessage?.({ data: JSON.stringify(data) } as MessageEvent)
    }

    emitRaw(raw: string) {
        this.onmessage?.({ data: raw } as MessageEvent)
    }
}

vi.stubGlobal('EventSource', MockEventSource)

const MERCURE_URL = 'https://localhost/.well-known/mercure'

describe('mercure', () => {
    beforeEach(() => {
        MockEventSource.instances = []
        vi.resetModules()
    })

    afterEach(() => {
        vi.unstubAllEnvs()
    })

    it('throws when VITE_MERCURE_PUBLIC_URL is not set', async () => {
        vi.stubEnv('VITE_MERCURE_PUBLIC_URL', '')
        await expect(import('./mercure')).rejects.toThrow('VITE_MERCURE_PUBLIC_URL')
    })

    it('subscribe opens EventSource with withCredentials and correct topic', async () => {
        vi.stubEnv('VITE_MERCURE_PUBLIC_URL', MERCURE_URL)
        const { subscribe } = await import('./mercure')

        subscribe('/notifications/abc', vi.fn())

        const es = MockEventSource.instances[0]
        expect(es.withCredentials).toBe(true)
        expect(es.url).toContain('topic=')
        expect(decodeURIComponent(es.url)).toContain('/notifications/abc')
    })

    it('subscribe dispatches parsed message to onMessage', async () => {
        vi.stubEnv('VITE_MERCURE_PUBLIC_URL', MERCURE_URL)
        const { subscribe } = await import('./mercure')
        const onMessage = vi.fn()

        subscribe('/notifications/123', onMessage)

        MockEventSource.instances[0].emit({ op: 'created', id: '1' })

        expect(onMessage).toHaveBeenCalledWith({ op: 'created', id: '1' })
    })

    it('unsubscribe closes EventSource', async () => {
        vi.stubEnv('VITE_MERCURE_PUBLIC_URL', MERCURE_URL)
        const { subscribe } = await import('./mercure')

        const unsub = subscribe('/notifications/123', vi.fn())
        const es = MockEventSource.instances[0]

        unsub()

        expect(es.close).toHaveBeenCalledOnce()
    })

    it('malformed payload triggers onError and does not crash', async () => {
        vi.stubEnv('VITE_MERCURE_PUBLIC_URL', MERCURE_URL)
        const { subscribe } = await import('./mercure')
        const onError = vi.fn()

        subscribe('/notifications/123', vi.fn(), { onError })

        expect(() => {
            MockEventSource.instances[0].emitRaw('not { valid json')
        }).not.toThrow()

        expect(onError).toHaveBeenCalledOnce()
    })
})
