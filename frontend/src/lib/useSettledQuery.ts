import { AuthContext } from '@/contexts/auth/AuthContext'
import { useQuery, type UseQueryOptions } from '@tanstack/react-query'
import { useContext } from 'react'

type SettledQueryOptions<TData, TError> = UseQueryOptions<TData, TError> & {
    requireAuth?: boolean
}

export const useSettledQuery = <TData, TError = Error>({
    requireAuth = true,
    ...options
}: SettledQueryOptions<TData, TError>) => {
    const { isLoading, isAuthenticated } = useContext(AuthContext)
    const gate = requireAuth ? isAuthenticated : !isLoading
    return useQuery<TData, TError>({
        ...options,
        enabled: gate && (options.enabled ?? true),
    })
}
