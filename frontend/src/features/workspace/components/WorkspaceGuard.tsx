import { Navigate, Outlet, useParams } from 'react-router'
import { useWorkspace } from '../queries'

export function WorkspaceGuard() {
    const { id } = useParams<{ id: string }>()
    const { isLoading, isError } = useWorkspace(id!)

    if (isLoading) return <div>Loading...</div>
    if (isError) return <Navigate to="/" replace />

    return <Outlet />
}
