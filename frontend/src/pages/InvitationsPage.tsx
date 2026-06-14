import { MobileLayout } from '@/components/MobileLayout'
import { PageHeader } from '@/components/PageHeader'
import { InvitationsPage as InvitationsFeature } from '@/features/membership/components/InvitationsPage'
import { useIsMobile } from '@/hooks/useIsMobile'
import { useNavigate } from 'react-router'

export default function InvitationsPage() {
    const isMobile = useIsMobile()
    const navigate = useNavigate()

    if (isMobile) {
        return (
            <MobileLayout title="Invitations" onBack={() => void navigate(-1)}>
                <div className="flex flex-col gap-4 p-4">
                    <InvitationsFeature />
                </div>
            </MobileLayout>
        )
    }

    return (
        <div className="flex flex-col gap-4 p-6">
            <PageHeader title="Invitations" />
            <InvitationsFeature />
        </div>
    )
}
