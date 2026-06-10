import { Button } from '@/components/ui/button'
import { DeleteProjectDialog } from '@/features/project/components/DeleteProjectDialog'
import { ProjectCard } from '@/features/project/components/ProjectCard'
import { ProjectEmptyState } from '@/features/project/components/ProjectEmptyState'
import { ProjectFormModal } from '@/features/project/components/ProjectFormModal'
import { ProjectMembersDialog } from '@/features/project/components/ProjectMembersDialog'
import { useWorkspaceProjectsPage } from '@/features/project/hooks/useWorkspaceProjectsPage'
import type { ProjectStatus } from '@/features/project/types'
import { Plus, PlusCircle } from 'lucide-react'
import { useState } from 'react'

const FILTERS = [
    { value: 'all', label: 'All' },
    { value: 'active', label: 'Active' },
    { value: 'draft', label: 'Draft' },
    { value: 'archived', label: 'Archived' },
] as const

type Filter = (typeof FILTERS)[number]['value']

export default function WorkspaceProjectsPage() {
    const {
        workspaceId,
        projects,
        isLoading,
        createOpen,
        setCreateOpen,
        editProject,
        setEditProject,
        deleteProject,
        setDeleteProject,
        manageMembersProject,
        setManageMembersProject,
        canCreateProject,
        canManageProject,
        changeStatus,
        canManageMembers,
    } = useWorkspaceProjectsPage()

    const [filter, setFilter] = useState<Filter>('all')

    if (isLoading) return null

    const filtered =
        filter === 'all' ? (projects ?? []) : (projects ?? []).filter((p) => p.status === filter)

    return (
        <div className="flex flex-col gap-5 p-6">
            <div className="flex items-center justify-between">
                <h1 className="text-base font-semibold tracking-tight">Projects</h1>
                {canCreateProject() && (
                    <Button
                        size="lg"
                        className="cursor-pointer"
                        onClick={() => setCreateOpen(true)}
                    >
                        <Plus />
                        New project
                    </Button>
                )}
            </div>

            <div className="flex items-center gap-2">
                {FILTERS.map(({ value, label }) => (
                    <Button
                        key={value}
                        size="sm"
                        onClick={() => setFilter(value)}
                        className={`h-7 cursor-pointer rounded-full px-2.5 text-xs font-medium ${
                            filter === value
                                ? 'border-primary/30 bg-primary/10 text-primary hover:bg-primary/15 hover:text-primary dark:bg-primary/20 dark:border-primary/50 dark:hover:bg-primary/25'
                                : 'border-border bg-muted text-muted-foreground hover:bg-muted hover:text-foreground dark:bg-transparent dark:hover:bg-transparent'
                        }`}
                        variant="outline"
                    >
                        {label}
                    </Button>
                ))}
            </div>

            {filtered.length > 0 ? (
                <div className="grid [grid-template-columns:repeat(auto-fill,minmax(240px,1fr))] gap-3">
                    {filtered.map((project) => (
                        <ProjectCard
                            key={project.id}
                            project={project}
                            workspaceId={workspaceId}
                            canManage={canManageProject(project)}
                            canManageMembers={canManageMembers(project)}
                            onEdit={() => setEditProject(project)}
                            onDelete={() => setDeleteProject(project)}
                            onManageMembers={() => setManageMembersProject(project)}
                            onChangeStatus={(status: ProjectStatus) =>
                                changeStatus(project, status)
                            }
                        />
                    ))}
                    {canCreateProject() && (
                        <Button
                            variant="outline"
                            onClick={() => setCreateOpen(true)}
                            className="border-border text-muted-foreground hover:border-primary/40 hover:text-foreground h-auto min-h-[132px] w-full cursor-pointer flex-col gap-2 rounded-[calc(var(--radius))] border-dashed bg-transparent shadow-none hover:bg-transparent dark:bg-transparent dark:hover:bg-transparent"
                        >
                            <PlusCircle className="size-5" />
                            <span className="text-sm">New project</span>
                        </Button>
                    )}
                </div>
            ) : projects && projects.length === 0 ? (
                <ProjectEmptyState
                    onNew={canCreateProject() ? () => setCreateOpen(true) : undefined}
                />
            ) : (
                <p className="text-muted-foreground text-sm">No projects match this filter.</p>
            )}

            <ProjectFormModal
                open={createOpen}
                onOpenChange={setCreateOpen}
                workspaceId={workspaceId}
            />

            {editProject && (
                <ProjectFormModal
                    open={!!editProject}
                    onOpenChange={(open) => !open && setEditProject(null)}
                    workspaceId={workspaceId}
                    project={editProject}
                />
            )}

            {deleteProject && (
                <DeleteProjectDialog
                    open={!!deleteProject}
                    onOpenChange={(open) => !open && setDeleteProject(null)}
                    workspaceId={workspaceId}
                    project={deleteProject}
                />
            )}

            {manageMembersProject && (
                <ProjectMembersDialog
                    open={!!manageMembersProject}
                    onOpenChange={(open) => !open && setManageMembersProject(null)}
                    workspaceId={workspaceId}
                    project={manageMembersProject}
                />
            )}
        </div>
    )
}
