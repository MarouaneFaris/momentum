import { useState } from 'react'
import { useNavigate, useParams } from 'react-router'
import { useWorkspaces } from '../queries'
import { workspaceStorage } from '../workspaceStorage'

export const useWorkspaceSwitcher = () => {
    const { id: currentIdFromParams } = useParams<{ id: string }>()
    const currentId = currentIdFromParams ?? workspaceStorage.read() ?? undefined
    const { data: workspaces } = useWorkspaces()
    const navigate = useNavigate()
    const [isModalOpen, setIsModalOpen] = useState(false)

    const current = workspaces?.find((w) => w.id === currentId)

    const handleSelect = (workspaceId: string) => {
        workspaceStorage.write(workspaceId)
        void navigate(`/workspaces/${workspaceId}/dashboard`)
    }

    return { current, workspaces, isModalOpen, setIsModalOpen, handleSelect }
}
