import { useAuth } from '@/features/auth/queries'
import React, { type ReactNode } from 'react'
import { AuthContext } from './AuthContext'

export const AuthProvider: React.FC<{ children: ReactNode }> = ({ children }) => {
    const { isLoading, isError, data: user } = useAuth()

    return (
        <AuthContext.Provider
            value={{
                user,
                isLoading,
                isAuthenticated: !isLoading && !isError && !!user,
            }}
        >
            {children}
        </AuthContext.Provider>
    )
}
