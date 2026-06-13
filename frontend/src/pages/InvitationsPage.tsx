import { PageHeader } from '@/components/PageHeader'
import { InvitationsPage as InvitationsFeature } from '@/features/membership/components/InvitationsPage'

export default function InvitationsPage() {
    return (
        <div className="flex flex-col gap-4 p-4 md:p-6">
            <PageHeader title="Invitations" />
            <InvitationsFeature />
        </div>
    )
}
