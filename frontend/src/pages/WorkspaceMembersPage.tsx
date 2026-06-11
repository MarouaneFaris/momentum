import { useContext } from 'react'
import { useParams } from 'react-router'
import { Info } from 'lucide-react'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Badge } from '@/components/ui/badge'
import { AuthContext } from '@/contexts/auth/AuthContext'
import { useWorkspace } from '@/features/workspace/queries'
import { useWorkspaceMembers, useWorkspaceInvitations } from '@/features/membership/queries'
import { MembersTable } from '@/features/membership/components/MembersTable'
import { InvitationsTable } from '@/features/membership/components/InvitationsTable'
import { InviteForm } from '@/features/membership/components/InviteForm'
import { MobileMembersView } from '@/features/membership/components/MobileMembersView'
import { useIsMobile } from '@/hooks/useIsMobile'

export default function WorkspaceMembersPage() {
    const { id } = useParams<{ id: string }>()
    const { user } = useContext(AuthContext)
    const { data: workspace } = useWorkspace(id!)
    const isOwner = workspace?.role === 'owner'
    const { data: members } = useWorkspaceMembers(id!)
    const { data: invitations } = useWorkspaceInvitations(id!, isOwner)
    const isMobile = useIsMobile()

    const pendingCount = invitations?.filter((inv) => inv.status === 'pending').length ?? 0

    if (isMobile) {
        return (
            <MobileMembersView
                workspaceId={id!}
                workspaceName={workspace?.name ?? ''}
                isOwner={isOwner}
                currentUserId={String(user?.id ?? '')}
            />
        )
    }

    return (
        <div className="flex flex-col gap-6 p-6">
            <div className="flex items-center justify-between">
                <div className="flex flex-col gap-0.5">
                    <h1 className="text-xl font-semibold">Members</h1>
                    {workspace && members && (
                        <p className="text-muted-foreground text-sm">
                            {workspace.name} · {members.length} member
                            {members.length !== 1 ? 's' : ''}
                        </p>
                    )}
                </div>
            </div>

            {!isOwner && (
                <div className="border-border bg-muted/50 text-muted-foreground flex items-center gap-2 rounded-md border px-4 py-3 text-sm">
                    <Info className="h-4 w-4 shrink-0" />
                    Only workspace owners can invite or remove members.
                </div>
            )}

            {isOwner ? (
                <Tabs defaultValue="members">
                    <TabsList className="h-auto w-full justify-start rounded-none border-b bg-transparent p-0">
                        <TabsTrigger
                            value="members"
                            className="data-[state=active]:border-primary data-[state=active]:text-primary -mb-px rounded-none border-b-2 border-transparent px-4 py-2.5 data-[state=active]:bg-transparent data-[state=active]:shadow-none"
                        >
                            Members
                        </TabsTrigger>
                        <TabsTrigger
                            value="invitations"
                            className="data-[state=active]:border-primary data-[state=active]:text-primary -mb-px gap-1.5 rounded-none border-b-2 border-transparent px-4 py-2.5 data-[state=active]:bg-transparent data-[state=active]:shadow-none"
                        >
                            Invitations
                            {pendingCount > 0 && (
                                <Badge className="ml-0.5 h-4 min-w-4 px-2 text-[10px]">
                                    {pendingCount}
                                </Badge>
                            )}
                        </TabsTrigger>
                    </TabsList>

                    <TabsContent value="members" className="mt-4 flex flex-col gap-4">
                        <InviteForm workspaceId={id!} />
                        <MembersTable
                            workspaceId={id!}
                            currentUserId={String(user?.id ?? '')}
                            isOwner={isOwner}
                            workspaceName={workspace?.name ?? ''}
                        />
                    </TabsContent>

                    <TabsContent value="invitations" className="mt-4">
                        <InvitationsTable workspaceId={id!} />
                    </TabsContent>
                </Tabs>
            ) : (
                <MembersTable
                    workspaceId={id!}
                    currentUserId={String(user?.id ?? '')}
                    isOwner={false}
                    workspaceName={workspace?.name ?? ''}
                />
            )}
        </div>
    )
}
