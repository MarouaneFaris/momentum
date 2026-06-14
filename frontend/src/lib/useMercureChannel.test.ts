import { renderHook, waitFor } from '@testing-library/react'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import api from '@/lib/api'
import { useMercureChannel } from './useMercureChannel'

vi.mock('@/lib/api', () => ({
    default: { get: vi.fn() },
}))

let capturedOnMessage: ((event: MessageEvent) => void) | null = null
let capturedOnError: ((event: Event) => void) | null = null
const mockEsClose = vi.fn()

class FakeEventSource {
    static lastInstance: FakeEventSource | null = null
    readonly url: string
    constructor(url: string) {
        this.url = url
        capturedOnMessage = null
        capturedOnError = null
        FakeEventSource.lastInstance = this
    }
    set onmessage(fn: (event: MessageEvent) => void) {
        capturedOnMessage = fn
    }
    get onmessage() {
        return capturedOnMessage ?? (() => {})
    }
    set onerror(fn: (event: Event) => void) {
        capturedOnError = fn
    }
    get onerror() {
        return capturedOnError ?? (() => {})
    }
    close = mockEsClose
}

beforeEach(() => {
    capturedOnMessage = null
    capturedOnError = null
    mockEsClose.mockClear()
    FakeEventSource.lastInstance = null
    vi.stubGlobal('EventSource', FakeEventSource)
    vi.stubEnv('VITE_MERCURE_PUBLIC_URL', 'http://localhost/.well-known/mercure')
    vi.mocked(api.get).mockResolvedValue({ expiresIn: 3600 })
})

afterEach(() => {
    vi.unstubAllGlobals()
    vi.unstubAllEnvs()
    vi.clearAllMocks()
})

describe('useMercureChannel', () => {
    it('fetches token and opens EventSource on mount', async () => {
        const onMessage = vi.fn()
        renderHook(() => useMercureChannel({ topic: '/notifications/user-1', onMessage }))

        await waitFor(() => expect(capturedOnMessage).not.toBeNull())
        expect(api.get).toHaveBeenCalledWith('/notifications/mercure-token')
        expect(FakeEventSource.lastInstance?.url).toContain('topic=%2Fnotifications%2Fuser-1')
    })

    it('dispatches parsed message to onMessage callback', async () => {
        const onMessage = vi.fn()
        renderHook(() => useMercureChannel({ topic: '/notifications/user-1', onMessage }))
        await waitFor(() => expect(capturedOnMessage).not.toBeNull())

        capturedOnMessage?.({ data: JSON.stringify({ op: 'created' }) } as MessageEvent)
        expect(onMessage).toHaveBeenCalledWith({ op: 'created' })
    })

    it('calls onError for malformed JSON', async () => {
        const onMessage = vi.fn()
        const onError = vi.fn()
        renderHook(() => useMercureChannel({ topic: '/notifications/user-1', onMessage, onError }))
        await waitFor(() => expect(capturedOnMessage).not.toBeNull())

        capturedOnMessage?.({ data: 'not-json{{{' } as MessageEvent)
        expect(onMessage).not.toHaveBeenCalled()
        expect(onError).toHaveBeenCalledTimes(1)
    })

    it('wires onerror on EventSource', async () => {
        const onMessage = vi.fn()
        const onError = vi.fn()
        renderHook(() => useMercureChannel({ topic: '/notifications/user-1', onMessage, onError }))
        await waitFor(() => expect(capturedOnError).not.toBeNull())

        capturedOnError?.({} as Event)
        expect(onError).toHaveBeenCalledTimes(1)
    })

    it('closes EventSource on unmount', async () => {
        const onMessage = vi.fn()
        const { unmount } = renderHook(() =>
            useMercureChannel({ topic: '/notifications/user-1', onMessage }),
        )
        await waitFor(() => expect(capturedOnMessage).not.toBeNull())

        unmount()
        expect(mockEsClose).toHaveBeenCalled()
    })

    it('skips connect when enabled=false', () => {
        const onMessage = vi.fn()
        renderHook(() =>
            useMercureChannel({ topic: '/notifications/user-1', onMessage, enabled: false }),
        )
        expect(api.get).not.toHaveBeenCalled()
    })

    it('schedules token refresh before TTL', async () => {
        vi.useFakeTimers()
        vi.mocked(api.get).mockResolvedValue({ expiresIn: 120 })
        const onMessage = vi.fn()
        renderHook(() => useMercureChannel({ topic: '/notifications/user-1', onMessage }))

        // flush initial connect() promise
        await vi.advanceTimersByTimeAsync(0)
        vi.mocked(api.get).mockClear()

        // advance past refresh interval: (120 - 30) * 1000 = 90_000ms
        await vi.advanceTimersByTimeAsync(91_000)

        expect(api.get).toHaveBeenCalledWith('/notifications/mercure-token')
        vi.useRealTimers()
    })
})
