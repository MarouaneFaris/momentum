import { StatsRow } from '@/features/task/components/StatsRow'
import { useWorkspaceTaskStats } from '@/features/task/queries'
import { useIsMobile } from '@/hooks/useIsMobile'
import { useParams } from 'react-router'

export default function WorkspaceDashboardPage() {
    const { id } = useParams<{ id: string }>()
    const isMobile = useIsMobile()
    const { data: stats, isLoading } = useWorkspaceTaskStats(id!)

    if (isLoading || !stats) return null

    return (
        <div className="flex flex-col gap-4 p-4 md:gap-5 md:p-6">
            <StatsRow stats={stats} isMobile={isMobile} />
        </div>
    )
}
