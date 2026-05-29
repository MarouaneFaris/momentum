import { Button } from '@/components/ui/button'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { ChevronDown, PlusIcon } from 'lucide-react'
import { useState } from 'react'
import { useNavigate, useParams } from 'react-router'
import { useWorkspaces } from '../queries'
import { workspaceStorage } from '../workspaceStorage'
import { CreateWorkspaceModal } from './CreateWorkspaceModal'

export function WorkspaceSwitcher() {
    const { id: currentId } = useParams<{ id: string }>()
    const { data: workspaces } = useWorkspaces()
    const { write } = workspaceStorage
    const navigate = useNavigate()
    const [isModalOpen, setIsModalOpen] = useState(false)

    const current = workspaces?.find((w) => w.id === currentId)

    const handleSelect = (workspaceId: string) => {
        write(workspaceId)
        void navigate(`/workspaces/${workspaceId}/dashboard`)
    }

    return (
        <>
            <DropdownMenu>
                <DropdownMenuTrigger asChild>
                    <Button variant="outline" className="w-full justify-between">
                        {current?.name ?? 'Select workspace'}
                        <ChevronDown className="ml-2 size-4" />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent className="w-56">
                    <DropdownMenuLabel>Workspaces</DropdownMenuLabel>
                    <DropdownMenuSeparator />
                    {workspaces?.map((workspace) => (
                        <DropdownMenuItem
                            key={workspace.id}
                            onClick={() => handleSelect(workspace.id)}
                        >
                            <span className="flex-1">{workspace.name}</span>
                            <span className="text-xs text-muted-foreground capitalize">
                                {workspace.role}
                            </span>
                        </DropdownMenuItem>
                    ))}
                    <DropdownMenuSeparator />
                    <DropdownMenuItem onClick={() => setIsModalOpen(true)}>
                        <PlusIcon className="size-4" />
                        Create workspace
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
            <CreateWorkspaceModal open={isModalOpen} onOpenChange={setIsModalOpen} />
        </>
    )
}
