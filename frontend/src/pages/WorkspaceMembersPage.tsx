import { InviteForm } from '@/features/membership/components/InviteForm'
import { PendingInvitationList } from '@/features/membership/components/PendingInvitationList'
import { useParams } from 'react-router'

export default function WorkspaceMembersPage() {
    const { id } = useParams<{ id: string }>()

    return (
        <div className="flex flex-col gap-8 p-6">
            <section className="flex flex-col gap-4">
                <h2 className="text-lg font-semibold">Invite member</h2>
                <InviteForm workspaceId={id!} />
            </section>
            <section className="flex flex-col gap-4">
                <h2 className="text-lg font-semibold">Pending invitations</h2>
                <PendingInvitationList workspaceId={id!} />
            </section>
        </div>
    )
}
