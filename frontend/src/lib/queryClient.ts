import { MutationCache, QueryCache, QueryClient } from '@tanstack/query-core'
import { toast } from 'sonner'
import ApiError from './ApiError'

const onError = (error: Error) => {
    if (!(error instanceof ApiError)) {
        toast.error('Network error, please try again.')
    }
}

const queryClient = new QueryClient({
    queryCache: new QueryCache({ onError }),
    mutationCache: new MutationCache({ onError }),
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
