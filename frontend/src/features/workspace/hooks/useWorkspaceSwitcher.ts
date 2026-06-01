import { useState } from 'react'
import { useNavigate } from 'react-router'
import { useWorkspaces } from '../queries'
import { workspaceStorage } from '../workspaceStorage'
import { useActiveWorkspaceId } from './useActiveWorkspaceId'

export const useWorkspaceSwitcher = () => {
    const currentId = useActiveWorkspaceId()
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
