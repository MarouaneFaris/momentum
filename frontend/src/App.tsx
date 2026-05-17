import { Button } from '@/components/ui/button'
import apiFetch from '@/lib/api'
import { useMutation, useQuery } from '@tanstack/react-query'
import { useState } from 'react'

function App() {
    const [auth, setAuth] = useState(false)

    const login = (credentials: { email: string; password: string }) => {
        return apiFetch('/login', 'POST', credentials)
    }

    const logout = () => {
        return apiFetch('/logout', 'POST')
    }

    const hello = () => {
        return apiFetch('/hello') as Promise<string>
    }

    const { data, isPending } = useQuery({
        queryKey: ['hello'],
        queryFn: hello,
        enabled: auth,
    })

    const mutation = useMutation({
        mutationFn: login,
        onSuccess: () => setAuth(true),
    })

    const mutationLogout = useMutation({
        mutationFn: logout,
        onSuccess: () => setAuth(false),
    })

    if (!auth) {
        return (
            <>
                <Button
                    type="button"
                    onClick={() =>
                        mutation.mutate({
                            email: 'marouanefaris@gmail.com',
                            password: 'mfaris',
                        })
                    }
                >
                    Login
                </Button>
            </>
        )
    }

    if (isPending) {
        return <p>Loading...</p>
    }

    return (
        <>
            <h1>{data}</h1>
            <Button type="button" variant="secondary" onClick={() => mutationLogout.mutate()}>
                Logout
            </Button>
        </>
    )
}

export default App
