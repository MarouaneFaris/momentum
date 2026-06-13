import { CardSkeleton } from '@/components/skeletons/CardSkeleton'
import { Navigate, Outlet, useParams } from 'react-router'
import { useWorkspace } from '../queries'

export function WorkspaceGuard() {
    const { id } = useParams<{ id: string }>()
    const { isLoading, isError } = useWorkspace(id!)

    if (isLoading) {
        return (
            <div className="flex flex-col gap-4 p-4 md:p-6">
                <CardSkeleton />
                <CardSkeleton />
            </div>
        )
    }
    if (isError) return <Navigate to="/" replace />

    return <Outlet />
}
