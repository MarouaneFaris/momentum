import { InvitationsPage as InvitationsFeature } from '@/features/membership/components/InvitationsPage'

export default function InvitationsPage() {
    return (
        <div className="flex flex-col gap-4 p-6">
            <h1 className="text-xl font-semibold">Invitations</h1>
            <InvitationsFeature />
        </div>
    )
}
