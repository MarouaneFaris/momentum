import { QueryClient } from '@tanstack/query-core'

const queryClient = new QueryClient()

declare global {
    interface Window {
        __TANSTACK_QUERY_CLIENT__: import('@tanstack/query-core').QueryClient
    }
}

if (import.meta.env.DEV) {
    window.__TANSTACK_QUERY_CLIENT__ = queryClient
}

export default queryClient
