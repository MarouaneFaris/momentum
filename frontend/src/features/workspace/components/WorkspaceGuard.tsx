import { useQueryClient } from '@tanstack/react-query'
import { useEffect } from 'react'
import { Navigate, Outlet, useParams } from 'react-router'
import { workspaceStorage } from '../workspaceStorage'
import { useWorkspace } from '../queries'

export function WorkspaceGuard() {
    const { id } = useParams<{ id: string }>()
    const queryClient = useQueryClient()
    const { isLoading, isError } = useWorkspace(id!)

    useEffect(() => {
        if (isError) {
            workspaceStorage.clear()
            void queryClient.invalidateQueries({ queryKey: ['workspaces'] })
        }
    }, [isError, queryClient])

    if (isLoading) return null
    if (isError) return <Navigate to="/" replace />

    return <Outlet />
}
