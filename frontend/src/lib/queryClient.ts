import { MutationCache, QueryCache, QueryClient } from '@tanstack/query-core'
import { toast } from 'sonner'
import ApiError from './ApiError'

const queryClient = new QueryClient({
    queryCache: new QueryCache({
        onError: (error) => {
            if (error instanceof ApiError) {
                if (error.status === 401) {
                    void queryClient.invalidateQueries({ queryKey: ['me'] })
                }
            } else {
                toast.error('Network error, please try again.')
            }
        },
    }),
    mutationCache: new MutationCache({
        onError: (error) => {
            if (!(error instanceof ApiError)) {
                toast.error('Network error, please try again.')
            }
        },
    }),
    defaultOptions: {
        queries: {
            retry: (_, error) => !(error instanceof ApiError),
        },
    },
})

declare global {
    interface Window {
        __TANSTACK_QUERY_CLIENT__: import('@tanstack/query-core').QueryClient
    }
}

if (import.meta.env.DEV) {
    window.__TANSTACK_QUERY_CLIENT__ = queryClient
}

export default queryClient
