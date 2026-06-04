import { useAuth } from '@/features/auth/queries'
import { useWorkspace } from '@/features/workspace/queries'
import { useQueryClient } from '@tanstack/react-query'
import { useState } from 'react'
import { useParams } from 'react-router'
import { useChangeProjectStatus, useProjects } from '../queries'
import type { Project, ProjectStatus } from '../types'

export function useWorkspaceProjectsPage() {
    const { id } = useParams<{ id: string }>()
    const { data: projects, isLoading } = useProjects(id!)
    const { data: workspace } = useWorkspace(id!)
    const { data: currentUser } = useAuth()
    const queryClient = useQueryClient()
    const changeStatusMutation = useChangeProjectStatus(id!)
    const [createOpen, setCreateOpen] = useState(false)
    const [editProject, setEditProject] = useState<Project | null>(null)
    const [deleteProject, setDeleteProject] = useState<Project | null>(null)

    function canCreateProject(): boolean {
        const role = workspace?.role
        return role === 'owner' || role === 'member'
    }

    function canManageProject(project: Project): boolean {
        const role = workspace?.role
        if (!role || role === 'guest') return false
        if (role === 'owner') return true
        return project.ownerUserId === currentUser?.id
    }

    function changeStatus(project: Project, status: ProjectStatus) {
        changeStatusMutation.mutate(
            { projectId: project.id, status },
            {
                onSuccess: (updated) => {
                    if (updated === null) return
                    queryClient.setQueryData<Project[]>(
                        ['workspaces', id!, 'projects'],
                        (prev) => prev?.map((p) => (p.id === updated.id ? updated : p)) ?? [],
                    )
                },
            },
        )
    }

    return {
        workspaceId: id!,
        projects,
        isLoading,
        createOpen,
        setCreateOpen,
        editProject,
        setEditProject,
        deleteProject,
        setDeleteProject,
        canCreateProject,
        canManageProject,
        changeStatus,
    }
}
