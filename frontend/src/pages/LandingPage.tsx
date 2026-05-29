import { Button } from '@/components/ui/button'
import { workspaceStorage } from '@/features/workspace/workspaceStorage'
import { useWorkspaces } from '@/features/workspace/queries'
import { Navigate } from 'react-router'

export default function LandingPage() {
    const { data: workspaces, isLoading, isError } = useWorkspaces()

    if (isLoading) {
        return <div>Loading...</div>
    }

    if (isError) {
        return <div>Failed to load workspaces. Please refresh.</div>
    }

    if (workspaces && workspaces.length > 0) {
        const lastId = workspaceStorage.read()
        const target = workspaces.find((w) => w.id === lastId) ?? workspaces[0]
        return <Navigate to={`/workspaces/${target.id}/dashboard`} replace />
    }

    return (
        <div className="flex flex-col items-center gap-4 text-center">
            <h1 className="text-2xl font-semibold">Welcome to Momentum</h1>
            <p className="text-muted-foreground">You don&apos;t have any workspaces yet.</p>
            <Button disabled>Create workspace</Button>
        </div>
    )
}
