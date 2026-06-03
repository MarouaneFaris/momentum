import { useAuth } from '@/features/auth/queries'
import { useWorkspace } from '@/features/workspace/queries'
import { useState } from 'react'
import { useParams } from 'react-router'
import { useProjects } from '../queries'
import type { Project } from '../types'

export function useWorkspaceProjectsPage() {
    const { id } = useParams<{ id: string }>()
    const { data: projects, isLoading } = useProjects(id!)
    const { data: workspace } = useWorkspace(id!)
    const { data: currentUser } = useAuth()
    const [createOpen, setCreateOpen] = useState(false)
    const [editProject, setEditProject] = useState<Project | null>(null)
    const [deleteProject, setDeleteProject] = useState<Project | null>(null)

    function canManageProject(project: Project): boolean {
        const role = workspace?.role
        if (!role || role === 'guest') return false
        if (role === 'owner') return true
        return project.ownerUserId === currentUser?.id
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
        canManageProject,
    }
}
