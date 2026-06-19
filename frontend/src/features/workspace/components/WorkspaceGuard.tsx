import { useQueryClient } from '@tanstack/react-query'
import { useEffect } from 'react'
import { Navigate, Outlet, useParams } from 'react-router'
import { useWorkspace } from '../queries'
import type { Workspace } from '../types'
import { workspaceStorage } from '../workspaceStorage'

export function WorkspaceGuard() {
    const { id } = useParams<{ id: string }>()
    const { isLoading, isError } = useWorkspace(id!)
    const queryClient = useQueryClient()

    useEffect(() => {
        if (!isError || !id) return
        // Access to this workspace was denied (e.g. the user was removed from it).
        // Drop it from the caches the landing page reads, otherwise the landing
        // page redirects straight back into it and we ping-pong on 403 forever.
        if (workspaceStorage.read() === id) workspaceStorage.clear()
        queryClient.setQueryData<Workspace[]>(['workspaces'], (list) =>
            list?.filter((w) => w.id !== id),
        )
    }, [isError, id, queryClient])

    if (isLoading) return null
    if (isError) return <Navigate to="/" replace />

    return <Outlet />
}
