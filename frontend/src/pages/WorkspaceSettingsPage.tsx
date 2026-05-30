import { WorkspaceSettingsForm } from '@/features/workspace/components/WorkspaceSettingsForm'
import { useWorkspace } from '@/features/workspace/queries'
import { useParams } from 'react-router'

export default function WorkspaceSettingsPage() {
    const { id } = useParams<{ id: string }>()
    const { data: workspace, isLoading } = useWorkspace(id!)

    if (isLoading) return <div>Loading...</div>
    if (!workspace) return null

    return (
        <div className="flex flex-col gap-6">
            <h1 className="text-2xl font-semibold">Settings</h1>
            <WorkspaceSettingsForm workspace={workspace} />
        </div>
    )
}
