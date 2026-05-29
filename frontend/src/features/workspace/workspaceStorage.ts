const STORAGE_KEY = 'lastVisitedWorkspaceId'

export const workspaceStorage = {
    read: (): string | null => localStorage.getItem(STORAGE_KEY),
    write: (id: string): void => {
        localStorage.setItem(STORAGE_KEY, id)
    },
}
