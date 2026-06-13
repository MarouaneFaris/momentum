import ApiError from './ApiError'
import type { ApiRoute } from './routes'

const BASE_URL = import.meta.env.VITE_API_URL as string

const apiFetch = async <T>(url: ApiRoute, options: RequestInit = {}): Promise<T | null> => {
    const response = await fetch(`${BASE_URL}${url}`, {
        ...options,
        credentials: 'include',
        headers: {
            ...options.headers,
            'Content-Type': 'application/json',
            'X-Request-ID': crypto.randomUUID(),
        },
    })

    if (response.status === 204 || response.headers.get('Content-Length') === '0') {
        return null
    }

    const json = (await response.json().catch(() => null)) as T | null

    if (!response.ok) {
        const err = json as {
            code?: string
            message?: string
            context?: Record<string, unknown>
        } | null
        const code = err?.code ?? 'UNKNOWN'
        const message = err?.message ?? 'Network response was not ok'
        const context = err?.context ?? {}
        throw new ApiError(code, response.status, message, context)
    }

    return json
}

const formatBody = (body?: unknown): string | undefined => (body ? JSON.stringify(body) : undefined)

const api = {
    get: <T>(url: ApiRoute, options?: RequestInit) =>
        apiFetch<T>(url, { ...options, method: 'GET' }),

    post: <T>(url: ApiRoute, body?: unknown, options?: RequestInit) =>
        apiFetch<T>(url, {
            ...options,
            method: 'POST',
            body: formatBody(body),
        }),

    put: <T>(url: ApiRoute, body?: unknown, options?: RequestInit) =>
        apiFetch<T>(url, {
            ...options,
            method: 'PUT',
            body: formatBody(body),
        }),

    patch: <T>(url: ApiRoute, body?: unknown, options?: RequestInit) =>
        apiFetch<T>(url, {
            ...options,
            method: 'PATCH',
            body: formatBody(body),
        }),

    delete: <T>(url: ApiRoute, options?: RequestInit) =>
        apiFetch<T>(url, { ...options, method: 'DELETE' }),
}

export default api
