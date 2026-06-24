import api from '@/lib/api'
import { useFormMutation } from '@/hooks/useFormMutation'
import type { ApiRoute } from '@/lib/routes'
import { zodResolver } from '@hookform/resolvers/zod'
import { useForm } from 'react-hook-form'
import z from 'zod'
import type { Task } from '../types'
import { taskStatusSchema } from '../taskStatus'

const schema = z.object({
    title: z.string().min(1, 'Title is required').max(255, 'Title must be 255 characters or fewer'),
    description: z.string().optional(),
    assigneeId: z.string().optional(),
    status: taskStatusSchema.optional(),
    dueDate: z.string().optional(),
})

export type TaskFormValues = z.infer<typeof schema>

type CreatePayload = { title: string; description?: string; assigneeId?: string }
type UpdatePayload = CreatePayload & {
    status?: string
    removeAssignee?: boolean
    dueDate?: string
    removeDueDate?: boolean
}

export type UseTaskFormOptions = {
    workspaceId: string
    projectId: string
    onSuccess: () => void
} & (
    | { mode?: 'create'; taskId?: undefined; initialValues?: undefined }
    | { mode: 'edit'; taskId: string; initialValues: TaskFormValues }
)

export function useTaskForm({ workspaceId, projectId, onSuccess, ...rest }: UseTaskFormOptions) {
    const isEdit = rest.mode === 'edit'
    const taskBase = `/workspaces/${workspaceId}/projects/${projectId}/tasks` as ApiRoute
    const invalidateKey = ['workspaces', workspaceId, 'projects', projectId, 'tasks']

    const form = useForm<TaskFormValues>({
        resolver: zodResolver(schema),
        defaultValues: isEdit
            ? rest.initialValues
            : { title: '', description: '', assigneeId: '', dueDate: '' },
    })

    const createMutation = useFormMutation({
        mutationFn: (data: CreatePayload) => api.post<Task>(taskBase, data),
        invalidateKey,
        onSuccess: () => {
            onSuccess()
            form.reset()
        },
    })

    const updateMutation = useFormMutation({
        mutationFn: (data: UpdatePayload) =>
            api.patch<Task>(`${taskBase}/${isEdit ? rest.taskId : ''}` as ApiRoute, data),
        invalidateKey,
        onSuccess,
    })

    const isPending = isEdit ? updateMutation.isPending : createMutation.isPending

    const onSubmit = (values: TaskFormValues) => {
        const assigneePayload: { assigneeId?: string; removeAssignee?: boolean } =
            isEdit && values.assigneeId === ''
                ? { removeAssignee: true }
                : { assigneeId: values.assigneeId || undefined }

        const dueDatePayload: { dueDate?: string; removeDueDate?: boolean } =
            isEdit && !values.dueDate
                ? { removeDueDate: true }
                : { dueDate: values.dueDate || undefined }

        const payload = {
            title: values.title,
            description: values.description || undefined,
            ...(values.status ? { status: values.status } : {}),
            ...assigneePayload,
            ...dueDatePayload,
        }

        if (isEdit) {
            updateMutation.mutate(payload)
        } else {
            createMutation.mutate({
                title: payload.title,
                description: payload.description,
                assigneeId: payload.assigneeId,
            })
        }
    }

    return { form, isPending, onSubmit }
}
