import { useEffect } from 'react'
import { useParams } from 'react-router'
import { useWorkspaces } from '../queries'
import { workspaceStorage } from '../workspaceStorage'

export const useActiveWorkspaceId = (): string | undefined => {
    const { id } = useParams<{ id: string }>()
    const { data: workspaces } = useWorkspaces()

    const stored = workspaceStorage.read() ?? undefined
    // Only trust stored after the workspaces list has loaded and validated the ID.
    // Without this gate, a stale stored ID (e.g. a deleted workspace) drives 404
    // fetches in useWorkspace/useProjects before the list comes back.
    const storedIsValid = workspaces?.some((w) => w.id === stored) ?? false
    const resolved = id ?? (storedIsValid ? stored : undefined) ?? workspaces?.[0]?.id

    useEffect(() => {
        if (resolved && resolved !== stored) {
            workspaceStorage.write(resolved)
        } else if (!resolved && stored && workspaces && !storedIsValid) {
            workspaceStorage.clear()
        }
    }, [resolved, stored, workspaces, storedIsValid])

    return resolved
}
