import { EmptyState } from '@/components/EmptyState'
import { Button } from '@/components/ui/button'
import { FolderOpen, Plus } from 'lucide-react'

type ProjectEmptyStateProps = {
    onNew?: () => void
}

export function ProjectEmptyState({ onNew }: ProjectEmptyStateProps) {
    return (
        <EmptyState
            icon={FolderOpen}
            title="No projects yet"
            description="Create your first project to get started."
            action={
                onNew && (
                    <Button size="lg" onClick={onNew}>
                        <Plus />
                        New project
                    </Button>
                )
            }
        />
    )
}
