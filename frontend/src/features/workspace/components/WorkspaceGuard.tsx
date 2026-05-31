import { Navigate, Outlet } from 'react-router'
import { useWorkspace } from '../queries'

export function WorkspaceGuard() {
    const { isLoading, isError } = useWorkspace()

    if (isLoading) return <div>Loading...</div>
    if (isError) return <Navigate to="/" replace />

    return <Outlet />
}
