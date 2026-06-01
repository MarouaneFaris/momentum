import { useEffect } from 'react'
import { useParams } from 'react-router'
import { useWorkspaces } from '../queries'
import { workspaceStorage } from '../workspaceStorage'

export const useActiveWorkspaceId = (): string | undefined => {
    const { id } = useParams<{ id: string }>()
    const { data: workspaces } = useWorkspaces()

    const stored = workspaceStorage.read() ?? undefined
    // Treat stored as valid only while workspaces are loading or stored ID is in the list.
    // This evicts a stale ID left by a previous user (e.g. after dev login switch).
    const storedIsValid = workspaces == null || workspaces.some((w) => w.id === stored)
    const resolved = id ?? (storedIsValid ? stored : undefined) ?? workspaces?.[0]?.id

    useEffect(() => {
        if (resolved && resolved !== stored) {
            workspaceStorage.write(resolved)
        }
    }, [resolved, stored])

    return resolved
}
