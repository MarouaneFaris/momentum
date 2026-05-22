import type { AuthResponse } from '@/features/auth/types'
import { createContext } from 'react'

type AuthContextValue = {
    user?: AuthResponse | null
    isLoading: boolean
    isAuthenticated: boolean
}

export const AuthContext = createContext<AuthContextValue>({
    user: null,
    isLoading: true,
    isAuthenticated: false,
})
