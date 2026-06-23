import { act, renderHook } from '@testing-library/react'
import { useForm } from 'react-hook-form'
import {
    AUTH_FIELD_KEYS,
    clearPersistedAuthFields,
    readPersistedField,
    usePersistedField,
} from './usePersistedField'

const renderPersisted = (field: 'email' | 'name', key: string) =>
    renderHook(() => {
        const form = useForm<{ email: string; name: string }>({
            defaultValues: { email: '', name: '' },
        })
        usePersistedField(form.watch, field, key)
        return form
    })

describe('usePersistedField', () => {
    beforeEach(() => {
        sessionStorage.clear()
    })

    it('mirrors the watched field into sessionStorage on change', () => {
        const { result } = renderPersisted('email', AUTH_FIELD_KEYS.email)

        act(() => result.current.setValue('email', 'user@example.com'))

        expect(sessionStorage.getItem(AUTH_FIELD_KEYS.email)).toBe('user@example.com')
    })

    it('restores a previously persisted value via readPersistedField', () => {
        sessionStorage.setItem(AUTH_FIELD_KEYS.email, 'restored@example.com')

        expect(readPersistedField(AUTH_FIELD_KEYS.email)).toBe('restored@example.com')
    })

    it('removes the key when the field is cleared', () => {
        const { result } = renderPersisted('email', AUTH_FIELD_KEYS.email)

        act(() => result.current.setValue('email', 'user@example.com'))
        act(() => result.current.setValue('email', ''))

        expect(sessionStorage.getItem(AUTH_FIELD_KEYS.email)).toBeNull()
    })

    it('only persists the field it is bound to', () => {
        const { result } = renderPersisted('email', AUTH_FIELD_KEYS.email)

        act(() => result.current.setValue('name', 'Ada'))

        expect(sessionStorage.getItem(AUTH_FIELD_KEYS.name)).toBeNull()
    })

    it('clearPersistedAuthFields wipes every auth field key', () => {
        sessionStorage.setItem(AUTH_FIELD_KEYS.email, 'user@example.com')
        sessionStorage.setItem(AUTH_FIELD_KEYS.name, 'Ada')

        clearPersistedAuthFields()

        expect(sessionStorage.getItem(AUTH_FIELD_KEYS.email)).toBeNull()
        expect(sessionStorage.getItem(AUTH_FIELD_KEYS.name)).toBeNull()
    })

    it('returns an empty string for an unset key', () => {
        expect(readPersistedField('auth:does-not-exist')).toBe('')
    })
})
