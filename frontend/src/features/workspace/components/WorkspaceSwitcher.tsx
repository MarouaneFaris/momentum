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
import { useWorkspaceSwitcher } from '../hooks/useWorkspaceSwitcher'
import { CreateWorkspaceModal } from './CreateWorkspaceModal'

export function WorkspaceSwitcher() {
    const { current, workspaces, isModalOpen, setIsModalOpen, handleSelect } =
        useWorkspaceSwitcher()

    if (!workspaces?.length) return null

    return (
        <>
            <DropdownMenu>
                <DropdownMenuTrigger asChild>
                    <Button
                        variant="ghost"
                        className="h-auto gap-1.5 px-2 py-1 text-sm font-normal"
                    >
                        <span
                            className="flex h-[18px] w-[18px] flex-shrink-0 items-center justify-center rounded text-[9px] font-semibold text-primary"
                            style={{ background: 'oklch(0.488 0.243 264.376 / 0.15)' }}
                        >
                            {(current?.name?.[0] ?? 'W').toUpperCase()}
                        </span>
                        <span className="font-medium text-foreground">
                            {current?.name ?? 'Select workspace'}
                        </span>
                        <ChevronDown className="h-3 w-3 text-muted-foreground" />
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
