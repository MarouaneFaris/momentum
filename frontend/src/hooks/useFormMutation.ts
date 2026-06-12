import ApiError from '@/lib/ApiError'
import { copyFor } from '@/lib/errorCopy'
import { type QueryKey, useMutation, useQueryClient } from '@tanstack/react-query'
import { toast } from 'sonner'

type Options<TVariables, TData> = {
    mutationFn: (variables: TVariables) => Promise<TData>
    invalidateKey?: QueryKey
    onSuccess?: (data: TData) => void
}

export function useFormMutation<TVariables, TData = unknown>({
    mutationFn,
    invalidateKey,
    onSuccess,
}: Options<TVariables, TData>) {
    const queryClient = useQueryClient()

    const { mutate, isPending } = useMutation<TData, Error, TVariables>({
        mutationFn,
        onSuccess(data) {
            if (invalidateKey) {
                void queryClient.invalidateQueries({ queryKey: invalidateKey })
            }
            onSuccess?.(data)
        },
        onError(error) {
            if (error instanceof ApiError) {
                toast.error(copyFor(error.code))
            }
        },
    })

    return { mutate, isPending }
}
