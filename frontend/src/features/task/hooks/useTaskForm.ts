import { useFormMutation } from '@/hooks/useFormMutation'
import api from '@/lib/api'
import type { ApiRoute } from '@/lib/routes'
import { zodResolver } from '@hookform/resolvers/zod'
import { useForm } from 'react-hook-form'
import z from 'zod'
import { taskStatusSchema } from '../taskStatus'
import type {
    CreatePayload,
    Task,
    TaskFormValues,
    UpdatePayload,
    UseTaskFormOptions,
} from '../types'

const schema = z.object({
    title: z.string().min(1, 'Title is required').max(255, 'Title must be 255 characters or fewer'),
    description: z.string().optional(),
    assigneeId: z.string().optional(),
    status: taskStatusSchema.optional(),
    dueDate: z.string().optional(),
})

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
                dueDate: values.dueDate || undefined,
            })
        }
    }

    return { form, isPending, onSubmit }
}
