import { useEffect } from 'react'
import { useParams } from 'react-router'
import { useWorkspaces } from '../queries'
import { workspaceStorage } from '../workspaceStorage'

export const useActiveWorkspaceId = (): string | undefined => {
    const { id } = useParams<{ id: string }>()
    const { data: workspaces } = useWorkspaces()

    const stored = workspaceStorage.read() ?? undefined
    const resolved = id ?? stored ?? workspaces?.[0]?.id

    useEffect(() => {
        if (resolved && resolved !== stored) {
            workspaceStorage.write(resolved)
        }
    }, [resolved, stored])

    return resolved
}
