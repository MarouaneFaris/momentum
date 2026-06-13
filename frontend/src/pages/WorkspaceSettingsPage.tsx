import { CardSkeleton } from '@/components/skeletons/CardSkeleton'
import { PageHeader } from '@/components/PageHeader'
import { Badge } from '@/components/ui/badge'
import { useMyInvitations } from '@/features/membership/queries'
import { DeleteWorkspaceZone } from '@/features/workspace/components/DeleteWorkspaceZone'
import { WorkspaceSettingsForm } from '@/features/workspace/components/WorkspaceSettingsForm'
import { useWorkspace } from '@/features/workspace/queries'
import { useIsMobile } from '@/hooks/useIsMobile'
import { ChevronRight, Mail, Users } from 'lucide-react'
import { Link, useParams } from 'react-router'

export default function WorkspaceSettingsPage() {
    const { id } = useParams<{ id: string }>()
    const { data: workspace, isLoading } = useWorkspace(id!)
    const isMobile = useIsMobile()
    const { data: invitations } = useMyInvitations()
    const pendingCount = invitations?.length ?? 0

    if (isLoading) {
        return (
            <div className="flex flex-col gap-8 p-4 md:p-6">
                <PageHeader title="Settings" />
                <CardSkeleton />
                <CardSkeleton />
            </div>
        )
    }
    if (!workspace) return null

    return (
        <div className="flex flex-col gap-8 p-4 md:p-6">
            <PageHeader title="Settings" />

            {isMobile && (
                <div className="flex flex-col gap-1">
                    <p className="text-muted-foreground px-1 text-xs font-medium tracking-wide uppercase">
                        Workspace
                    </p>
                    <div className="bg-card divide-border divide-y overflow-hidden rounded-[var(--radius)] border">
                        <Link
                            to={`/workspaces/${id}/members`}
                            className="hover:bg-muted flex items-center gap-3 px-3 py-3"
                        >
                            <Users className="text-muted-foreground size-4 shrink-0" />
                            <span className="text-foreground flex-1 text-sm font-medium">
                                Members
                            </span>
                            <ChevronRight className="text-muted-foreground size-4 shrink-0" />
                        </Link>
                        <Link
                            to="/invitations"
                            className="hover:bg-muted flex items-center gap-3 px-3 py-3"
                        >
                            <Mail className="text-muted-foreground size-4 shrink-0" />
                            <span className="text-foreground flex-1 text-sm font-medium">
                                Invitations
                            </span>
                            {pendingCount > 0 && (
                                <Badge className="h-4 min-w-4 px-2 text-[10px]">
                                    {pendingCount}
                                </Badge>
                            )}
                            <ChevronRight className="text-muted-foreground size-4 shrink-0" />
                        </Link>
                    </div>
                </div>
            )}

            <WorkspaceSettingsForm workspace={workspace} />
            {workspace.role === 'owner' && <DeleteWorkspaceZone workspace={workspace} />}
        </div>
    )
}
