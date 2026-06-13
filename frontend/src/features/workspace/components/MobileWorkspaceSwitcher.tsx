import { BottomSheet } from '@/components/BottomSheet'
import { Button } from '@/components/ui/button'
import { ChevronDown, PlusIcon } from 'lucide-react'
import { useState } from 'react'
import { CreateWorkspaceModal } from './CreateWorkspaceModal'
import { useWorkspaceSwitcher } from '../hooks/useWorkspaceSwitcher'

export function MobileWorkspaceSwitcher() {
    const { current, workspaces, isModalOpen, setIsModalOpen, handleSelect } =
        useWorkspaceSwitcher()
    const [sheetOpen, setSheetOpen] = useState(false)

    if (!workspaces?.length) return null

    return (
        <>
            <button
                type="button"
                onClick={() => setSheetOpen(true)}
                className="flex items-center gap-1 text-sm font-medium"
            >
                <span
                    className="text-primary flex h-[18px] w-[18px] shrink-0 items-center justify-center rounded text-[9px] font-semibold"
                    style={{ background: 'oklch(0.488 0.243 264.376 / 0.15)' }}
                >
                    {(current?.name?.[0] ?? 'W').toUpperCase()}
                </span>
                <span className="text-foreground max-w-[120px] truncate font-medium">
                    {current?.name ?? 'Select workspace'}
                </span>
                <ChevronDown className="text-muted-foreground h-3 w-3 shrink-0" />
            </button>

            <BottomSheet open={sheetOpen} onOpenChange={setSheetOpen} title="Workspaces">
                <div className="flex flex-col pb-6">
                    {workspaces?.map((workspace) => (
                        <button
                            key={workspace.id}
                            type="button"
                            onClick={() => {
                                handleSelect(workspace.id)
                                setSheetOpen(false)
                            }}
                            className="hover:bg-muted flex items-center gap-3 px-4 py-3 text-left"
                        >
                            <span
                                className="text-primary flex h-7 w-7 shrink-0 items-center justify-center rounded text-xs font-semibold"
                                style={{ background: 'oklch(0.488 0.243 264.376 / 0.15)' }}
                            >
                                {workspace.name[0].toUpperCase()}
                            </span>
                            <span className="text-foreground flex-1 text-sm font-medium">
                                {workspace.name}
                            </span>
                            <span className="text-muted-foreground text-xs capitalize">
                                {workspace.role}
                            </span>
                        </button>
                    ))}
                    <div className="border-border mx-4 my-2 border-t" />
                    <Button
                        variant="ghost"
                        className="text-muted-foreground hover:text-foreground mx-2 justify-start gap-2"
                        onClick={() => {
                            setSheetOpen(false)
                            setIsModalOpen(true)
                        }}
                    >
                        <PlusIcon className="size-4" />
                        Create workspace
                    </Button>
                </div>
            </BottomSheet>

            <CreateWorkspaceModal open={isModalOpen} onOpenChange={setIsModalOpen} />
        </>
    )
}
