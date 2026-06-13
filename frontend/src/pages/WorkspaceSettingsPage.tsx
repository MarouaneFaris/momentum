import { PageHeader } from '@/components/PageHeader'
import { DeleteWorkspaceZone } from '@/features/workspace/components/DeleteWorkspaceZone'
import { WorkspaceSettingsForm } from '@/features/workspace/components/WorkspaceSettingsForm'
import { useWorkspace } from '@/features/workspace/queries'
import { useParams } from 'react-router'

export default function WorkspaceSettingsPage() {
    const { id } = useParams<{ id: string }>()
    const { data: workspace, isLoading } = useWorkspace(id!)

    if (isLoading) return <div>Loading...</div>
    if (!workspace) return null

    return (
        <div className="flex flex-col gap-8 p-4 md:p-6">
            <PageHeader title="Settings" />
            <WorkspaceSettingsForm workspace={workspace} />
            {workspace.role === 'owner' && <DeleteWorkspaceZone workspace={workspace} />}
        </div>
    )
}
