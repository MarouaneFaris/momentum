import { Navigate, Outlet, useParams } from 'react-router'
import { workspaceStorage } from '../workspaceStorage'
import { useWorkspace, useWorkspaces } from '../queries'

export function WorkspaceGuard() {
    const { id } = useParams<{ id: string }>()
    const { isLoading, isError } = useWorkspace(id!)
    const { data: workspaces, isLoading: workspacesLoading } = useWorkspaces()

    if (isLoading || (isError && workspacesLoading)) return null

    if (isError) {
        workspaceStorage.clear()
        const fallback = workspaces?.find((w) => w.id !== id)
        return <Navigate to={fallback ? `/workspaces/${fallback.id}/dashboard` : '/'} replace />
    }

    return <Outlet />
}
