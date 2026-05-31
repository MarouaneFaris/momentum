import { DeleteWorkspaceZone } from '@/features/workspace/components/DeleteWorkspaceZone'
import { WorkspaceSettingsForm } from '@/features/workspace/components/WorkspaceSettingsForm'
import { useWorkspace } from '@/features/workspace/queries'

export default function WorkspaceSettingsPage() {
    const { data: workspace, isLoading } = useWorkspace()

    if (isLoading) return <div>Loading...</div>
    if (!workspace) return null

    return (
        <div className="flex flex-col gap-8">
            <h1 className="text-2xl font-semibold">Settings</h1>
            <WorkspaceSettingsForm workspace={workspace} />
            {workspace.role === 'owner' && <DeleteWorkspaceZone workspace={workspace} />}
        </div>
    )
}
