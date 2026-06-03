import { useState } from 'react'
import { useParams } from 'react-router'
import { useProjects } from '../queries'
import type { Project } from '../types'

export function useWorkspaceProjectsPage() {
    const { id } = useParams<{ id: string }>()
    const { data: projects, isLoading } = useProjects(id!)
    const [createOpen, setCreateOpen] = useState(false)
    const [editProject, setEditProject] = useState<Project | null>(null)
    const [deleteProject, setDeleteProject] = useState<Project | null>(null)

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
    }
}
