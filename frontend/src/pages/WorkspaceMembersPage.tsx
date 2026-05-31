import { MemberList } from '@/features/membership/components/MemberList'
import { InviteForm } from '@/features/membership/components/InviteForm'
import { PendingInvitationList } from '@/features/membership/components/PendingInvitationList'
import { useWorkspace } from '@/features/workspace/queries'
import { AuthContext } from '@/contexts/auth/AuthContext'
import { useContext } from 'react'
import { useParams } from 'react-router'

export default function WorkspaceMembersPage() {
    const { id } = useParams<{ id: string }>()
    const { user } = useContext(AuthContext)
    const { data: workspace } = useWorkspace(id!)
    const isOwner = workspace?.role === 'owner'

    return (
        <div className="flex flex-col gap-8 p-6">
            <section className="flex flex-col gap-4">
                <h2 className="text-lg font-semibold">Members</h2>
                <MemberList
                    workspaceId={id!}
                    currentUserId={String(user?.id ?? '')}
                    isOwner={isOwner}
                />
            </section>
            {isOwner && (
                <>
                    <section className="flex flex-col gap-4">
                        <h2 className="text-lg font-semibold">Invite member</h2>
                        <InviteForm workspaceId={id!} />
                    </section>
                    <section className="flex flex-col gap-4">
                        <h2 className="text-lg font-semibold">Pending invitations</h2>
                        <PendingInvitationList workspaceId={id!} />
                    </section>
                </>
            )}
        </div>
    )
}
