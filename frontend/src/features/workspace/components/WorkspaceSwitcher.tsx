import { Button } from '@/components/ui/button'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { ChevronDown } from 'lucide-react'
import { useNavigate, useParams } from 'react-router'
import { useLastVisitedWorkspace } from '../hooks/useLastVisitedWorkspace'
import { useWorkspaces } from '../queries'

export function WorkspaceSwitcher() {
    const { id: currentId } = useParams<{ id: string }>()
    const { data: workspaces } = useWorkspaces()
    const { write } = useLastVisitedWorkspace()
    const navigate = useNavigate()

    const current = workspaces?.find((w) => w.id === currentId)

    const handleSelect = (workspaceId: string) => {
        write(workspaceId)
        void navigate(`/workspaces/${workspaceId}/dashboard`)
    }

    return (
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
            </DropdownMenuContent>
        </DropdownMenu>
    )
}
