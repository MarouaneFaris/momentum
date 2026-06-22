import { useEffect } from 'react'
import type { FieldValues, Path, UseFormWatch } from 'react-hook-form'

/**
 * sessionStorage keys for auth form fields that should survive remounts and
 * tab discards/reloads. Email and name are low-sensitivity and shared between
 * the login and register forms so a value typed on one prefills the other.
 * Passwords are never persisted.
 */
export const AUTH_FIELD_KEYS = {
    email: 'auth:email',
    name: 'auth:name',
} as const

export const readPersistedField = (key: string): string => {
    if (typeof sessionStorage === 'undefined') return ''
    return sessionStorage.getItem(key) ?? ''
}

export const clearPersistedAuthFields = (): void => {
    if (typeof sessionStorage === 'undefined') return
    Object.values(AUTH_FIELD_KEYS).forEach((key) => sessionStorage.removeItem(key))
}

/**
 * Mirrors a single form field into sessionStorage on every change so its value
 * is restored when the component remounts (route navigation) or the tab is
 * reloaded/discarded by the browser after switching to another app.
 */
export const usePersistedField = <T extends FieldValues>(
    watch: UseFormWatch<T>,
    field: Path<T>,
    key: string,
): void => {
    useEffect(() => {
        const subscription = watch((value, { name }) => {
            if (name !== field) return

            const fieldValue = value[field]
            if (typeof fieldValue === 'string' && fieldValue) {
                sessionStorage.setItem(key, fieldValue)
            } else {
                sessionStorage.removeItem(key)
            }
        })

        return () => subscription.unsubscribe()
    }, [watch, field, key])
}
