import { Button } from '@/components/ui/button'
import { Fab } from '@/components/Fab'
import { FilterChips } from '@/components/FilterChips'
import { DeleteProjectDialog } from '@/features/project/components/DeleteProjectDialog'
import { ProjectCard } from '@/features/project/components/ProjectCard'
import { ProjectEmptyState } from '@/features/project/components/ProjectEmptyState'
import { ProjectFormModal } from '@/features/project/components/ProjectFormModal'
import { ProjectMembersDialog } from '@/features/project/components/ProjectMembersDialog'
import { useWorkspaceProjectsPage } from '@/features/project/hooks/useWorkspaceProjectsPage'
import type { ProjectStatus } from '@/features/project/types'
import { useIsMobile } from '@/hooks/useIsMobile'
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
    const isMobile = useIsMobile()

    if (isLoading) return null

    const filtered =
        filter === 'all' ? (projects ?? []) : (projects ?? []).filter((p) => p.status === filter)

    return (
        <div className="flex flex-col gap-4 p-4 md:gap-5 md:p-6">
            <div className="flex items-center justify-between">
                <h1 className="text-base font-semibold tracking-tight">Projects</h1>
                {canCreateProject() && (
                    <Button
                        size="lg"
                        className="hidden cursor-pointer md:flex"
                        onClick={() => setCreateOpen(true)}
                    >
                        <Plus />
                        New project
                    </Button>
                )}
            </div>

            <FilterChips
                options={FILTERS as unknown as { label: string; value: string }[]}
                value={filter}
                onChange={(v) => setFilter(v as Filter)}
            />

            {filtered.length > 0 ? (
                <div className="grid grid-cols-1 gap-3 md:[grid-template-columns:repeat(auto-fill,minmax(240px,1fr))]">
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
                    {canCreateProject() && !isMobile && (
                        <Button
                            variant="outline"
                            onClick={() => setCreateOpen(true)}
                            className="border-border text-muted-foreground hover:border-primary/40 hover:text-foreground dark:hover:border-primary/40 h-auto min-h-[132px] w-full cursor-pointer flex-col gap-2 rounded-[calc(var(--radius))] border-dashed bg-transparent shadow-none hover:bg-transparent dark:bg-transparent dark:hover:bg-transparent"
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

            {canCreateProject() && (
                <Fab icon={Plus} onClick={() => setCreateOpen(true)} hidden={!isMobile} />
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
