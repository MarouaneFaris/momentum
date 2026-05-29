import { useParams } from 'react-router'
import api from './api'

export const useWorkspaceApi = () => {
    const { id: workspaceId } = useParams<{ id: string }>()

    if (!workspaceId) {
        throw new Error('useWorkspaceApi must be used inside a /workspaces/:id route')
    }

    const prefix = `/workspaces/${workspaceId}` as const

    return {
        workspaceId,
        get: <T>(path: string, options?: RequestInit) => api.get<T>(`${prefix}${path}`, options),
        post: <T>(path: string, body?: unknown, options?: RequestInit) =>
            api.post<T>(`${prefix}${path}`, body, options),
        put: <T>(path: string, body?: unknown, options?: RequestInit) =>
            api.put<T>(`${prefix}${path}`, body, options),
        patch: <T>(path: string, body?: unknown, options?: RequestInit) =>
            api.patch<T>(`${prefix}${path}`, body, options),
        delete: <T>(path: string, options?: RequestInit) =>
            api.delete<T>(`${prefix}${path}`, options),
    }
}
