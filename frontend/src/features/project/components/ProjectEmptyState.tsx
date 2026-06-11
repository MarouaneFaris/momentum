import { Button } from '@/components/ui/button'
import { FolderOpen, Plus } from 'lucide-react'

type ProjectEmptyStateProps = {
    onNew?: () => void
}

export function ProjectEmptyState({ onNew }: ProjectEmptyStateProps) {
    return (
        <div className="flex flex-col items-center gap-4 py-16">
            <div className="bg-muted flex size-12 items-center justify-center rounded-full">
                <FolderOpen className="text-muted-foreground size-5" />
            </div>
            <div className="text-center">
                <p className="text-foreground text-sm font-medium">No projects yet</p>
                <p className="text-muted-foreground mt-1 text-xs">
                    Create your first project to get started.
                </p>
            </div>
            {onNew && (
                <Button size="lg" onClick={onNew}>
                    <Plus />
                    New project
                </Button>
            )}
        </div>
    )
}
