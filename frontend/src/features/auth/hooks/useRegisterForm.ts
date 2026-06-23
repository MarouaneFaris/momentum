import ApiError from '@/lib/ApiError'
import { copyFor } from '@/lib/errorCopy'
import { zodResolver } from '@hookform/resolvers/zod'
import { useForm } from 'react-hook-form'
import { useNavigate } from 'react-router'
import { toast } from 'sonner'
import z from 'zod'
import { useRegister } from '../queries'
import { AUTH_FIELD_KEYS, readPersistedField, usePersistedField } from './usePersistedField'

const schema = z.object({
    name: z.string().min(2, 'Name must be at least 2 characters'),
    email: z.email(),
    password: z.string().min(12, 'Password must be at least 12 characters'),
})

const navigateToLogin = (navigate: ReturnType<typeof useNavigate>, message?: string) => {
    // Keep the email so it prefills the login form; drop the name, it's not needed there.
    if (typeof sessionStorage !== 'undefined') sessionStorage.removeItem(AUTH_FIELD_KEYS.name)
    if (message) toast(message)
    void navigate('/login')
}

export const useRegisterForm = () => {
    const { mutate } = useRegister()
    const navigate = useNavigate()

    const {
        handleSubmit,
        register,
        watch,
        formState: { errors },
    } = useForm({
        resolver: zodResolver(schema),
        defaultValues: {
            name: readPersistedField(AUTH_FIELD_KEYS.name),
            email: readPersistedField(AUTH_FIELD_KEYS.email),
            password: '',
        },
    })

    usePersistedField(watch, 'name', AUTH_FIELD_KEYS.name)
    usePersistedField(watch, 'email', AUTH_FIELD_KEYS.email)

    const handleOnSubmit = (e: React.SubmitEvent<HTMLFormElement>) => {
        void handleSubmit(({ name, email, password }) =>
            mutate(
                { name, email, password },
                {
                    onSuccess: (data) => {
                        navigateToLogin(navigate, data?.message)
                    },
                    onError: (error) => {
                        if (error instanceof ApiError) {
                            // AUTH_DUPLICATE_EMAIL is intentionally treated as success to prevent email enumeration
                            if (error.code === 'AUTH_DUPLICATE_EMAIL') {
                                navigateToLogin(navigate)
                                return
                            }
                            toast.error(copyFor(error.code))
                        }
                    },
                },
            ),
        )(e)
    }

    return { register, handleOnSubmit, errors }
}
