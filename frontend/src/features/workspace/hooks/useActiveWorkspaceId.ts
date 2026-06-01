import { useParams } from 'react-router'
import { workspaceStorage } from '../workspaceStorage'

export const useActiveWorkspaceId = (): string | undefined =>
    useParams<{ id: string }>().id ?? workspaceStorage.read() ?? undefined
