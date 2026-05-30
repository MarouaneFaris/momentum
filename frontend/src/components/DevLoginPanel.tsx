import api from '@/lib/api'
import { useQueryClient } from '@tanstack/react-query'
import { useState } from 'react'

type DevUser = { id: string; email: string }

export default function DevLoginPanel() {
    const [open, setOpen] = useState(false)
    const [users, setUsers] = useState<DevUser[]>([])
    const queryClient = useQueryClient()

    if (!import.meta.env.DEV) return null

    const handleOpen = async () => {
        const result = await api.get<DevUser[]>('/dev/users')
        setUsers(result ?? [])
        setOpen(true)
    }

    const handleLoginAs = async (email: string) => {
        const alreadyLoggedIn = queryClient.getQueryData(['me']) != null
        await api.post('/dev/login-as', { email })
        if (alreadyLoggedIn) {
            window.location.reload()
        } else {
            await queryClient.invalidateQueries({ queryKey: ['me'] })
            setOpen(false)
        }
    }

    return (
        <div className="fixed bottom-4 right-4 z-50">
            {open ? (
                <div className="bg-background border rounded-lg shadow-lg p-3 min-w-48">
                    <div className="flex items-center justify-between mb-2">
                        <span className="text-xs font-semibold text-muted-foreground">
                            Dev Login
                        </span>
                        <button
                            onClick={() => setOpen(false)}
                            className="text-xs text-muted-foreground hover:text-foreground"
                        >
                            ✕
                        </button>
                    </div>
                    <ul className="space-y-1">
                        {users.map((user) => (
                            <li key={user.id}>
                                <button
                                    onClick={() => void handleLoginAs(user.email)}
                                    className="w-full text-left text-sm px-2 py-1 rounded hover:bg-muted truncate"
                                >
                                    {user.email}
                                </button>
                            </li>
                        ))}
                    </ul>
                </div>
            ) : (
                <button
                    onClick={() => void handleOpen()}
                    className="text-xs bg-muted text-muted-foreground px-2 py-1 rounded hover:bg-muted/80"
                >
                    Dev Login
                </button>
            )}
        </div>
    )
}
