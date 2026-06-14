import { EmptyState } from '@/components/EmptyState'
import { Fab } from '@/components/Fab'
import { FilterChips } from '@/components/FilterChips'
import { MobileLayout } from '@/components/MobileLayout'
import { Button } from '@/components/ui/button'
import { ClipboardList, Info, Plus } from 'lucide-react'
import type { Task, TaskStatus } from '../types'
import { TaskRow } from './TaskRow'

export type MobileFilter = 'all' | TaskStatus

const FILTER_OPTIONS = [
    { label: 'All', value: 'all' },
    { label: 'To do', value: 'todo' },
    { label: 'In progress', value: 'in-progress' },
    { label: 'Done', value: 'done' },
]

export function MobileTaskList({
    tasks,
    isEmpty,
    isGuest,
    filter,
    onFilterChange,
    onTaskTap,
    onNewTask,
    workspaceId,
    projectName,
}: {
    tasks: Task[]
    isEmpty: boolean
    isGuest: boolean
    filter: MobileFilter
    onFilterChange: (f: MobileFilter) => void
    onTaskTap: (taskId: string) => void
    onNewTask: () => void
    workspaceId: string
    projectName: string | null
}) {
    const filtered = filter === 'all' ? tasks : tasks.filter((t) => t.status === filter)

    return (
        <MobileLayout
            title={projectName ?? 'Tasks'}
            backHref={`/workspaces/${workspaceId}/projects`}
        >
            <div className="flex flex-col gap-3 p-4">
                {isGuest && (
                    <div className="bg-muted border-border text-muted-foreground flex items-center gap-2 rounded-[var(--radius)] border px-3 py-2 text-xs">
                        <Info className="size-3.5 shrink-0" />
                        You&apos;re viewing as a Guest — read-only access.
                    </div>
                )}

                <FilterChips
                    options={FILTER_OPTIONS}
                    value={filter}
                    onChange={(v) => onFilterChange(v as MobileFilter)}
                />

                {isEmpty ? (
                    <EmptyState
                        icon={ClipboardList}
                        title="No tasks yet"
                        description="Create a task to start tracking work for this project."
                        action={
                            !isGuest && (
                                <Button size="lg" onClick={onNewTask}>
                                    <Plus />
                                    New task
                                </Button>
                            )
                        }
                    />
                ) : filtered.length === 0 ? (
                    <p className="text-muted-foreground py-8 text-center text-sm">
                        No tasks match this filter.
                    </p>
                ) : (
                    <div className="flex flex-col gap-2">
                        {filtered.map((task) => (
                            <TaskRow key={task.id} task={task} onClick={() => onTaskTap(task.id)} />
                        ))}
                    </div>
                )}

                <Fab icon={Plus} onClick={onNewTask} hidden={isGuest} />
            </div>
        </MobileLayout>
    )
}
