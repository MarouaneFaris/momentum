import { fireEvent, render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { MobileTaskDetail } from './MobileTaskDetail'
import { MobileTaskList } from './MobileTaskList'
import type { Task, TaskDetail } from '../types'

vi.mock('@/features/task/hooks/useUpdateTaskStatus', () => ({
    useUpdateTaskStatus: () => ({ update: vi.fn(), isPending: false }),
}))

vi.mock('@/features/membership/queries', () => ({
    useWorkspaceMembers: () => ({ data: [] }),
}))

const makeTasks = (): Task[] => [
    {
        id: 'task-1',
        title: 'Write tests',
        status: 'todo',
        assignee: null,
        createdAt: '2026-01-01T00:00:00Z',
        creatorId: 'user-1',
    },
    {
        id: 'task-2',
        title: 'Fix the bug',
        status: 'in-progress',
        assignee: { id: 'user-2', name: 'Alice Smith' },
        createdAt: '2026-01-02T00:00:00Z',
        creatorId: 'user-2',
    },
    {
        id: 'task-3',
        title: 'Deploy to prod',
        status: 'done',
        assignee: null,
        createdAt: '2026-01-03T00:00:00Z',
        creatorId: 'user-1',
    },
]

const makeDetail = (overrides: Partial<TaskDetail> = {}): TaskDetail => ({
    id: 'task-1',
    title: 'Write tests',
    description: null,
    status: 'todo',
    creator: { id: 'user-1', name: 'Bob Jones' },
    assignee: null,
    createdAt: '2026-01-01T00:00:00Z',
    updatedAt: '2026-01-01T00:00:00Z',
    ...overrides,
})

describe('MobileTaskList', () => {
    const defaultProps = {
        tasks: makeTasks(),
        isEmpty: false,
        isGuest: false,
        filter: 'all' as const,
        onFilterChange: vi.fn(),
        onTaskTap: vi.fn(),
        onNewTask: vi.fn(),
    }

    it('renders Fab for member/owner (non-guest)', () => {
        render(<MobileTaskList {...defaultProps} />)
        expect(screen.getByRole('button', { name: /action/i })).toBeInTheDocument()
    })

    it('hides Fab for guest', () => {
        render(<MobileTaskList {...defaultProps} isGuest={true} />)
        expect(screen.queryByRole('button', { name: /action/i })).not.toBeInTheDocument()
    })

    it('shows all tasks when filter is "all"', () => {
        render(<MobileTaskList {...defaultProps} filter="all" />)
        expect(screen.getByText('Write tests')).toBeInTheDocument()
        expect(screen.getByText('Fix the bug')).toBeInTheDocument()
        expect(screen.getByText('Deploy to prod')).toBeInTheDocument()
    })

    it('filters to only todo tasks when filter is "todo"', () => {
        render(<MobileTaskList {...defaultProps} filter="todo" />)
        expect(screen.getByText('Write tests')).toBeInTheDocument()
        expect(screen.queryByText('Fix the bug')).not.toBeInTheDocument()
        expect(screen.queryByText('Deploy to prod')).not.toBeInTheDocument()
    })

    it('filters to only in-progress tasks when filter is "in-progress"', () => {
        render(<MobileTaskList {...defaultProps} filter="in-progress" />)
        expect(screen.queryByText('Write tests')).not.toBeInTheDocument()
        expect(screen.getByText('Fix the bug')).toBeInTheDocument()
        expect(screen.queryByText('Deploy to prod')).not.toBeInTheDocument()
    })

    it('calls onFilterChange when a chip is clicked', () => {
        const onFilterChange = vi.fn()
        render(<MobileTaskList {...defaultProps} onFilterChange={onFilterChange} />)
        // First "To do" occurrence is the FilterChip button (before task rows)
        fireEvent.click(screen.getAllByText('To do')[0])
        expect(onFilterChange).toHaveBeenCalledWith('todo')
    })

    it('shows empty state when isEmpty is true', () => {
        render(<MobileTaskList {...defaultProps} tasks={[]} isEmpty={true} />)
        expect(screen.getByText('No tasks yet')).toBeInTheDocument()
    })

    it('shows "No tasks match" when tasks exist but filter returns none', () => {
        render(
            <MobileTaskList
                {...defaultProps}
                tasks={[makeTasks()[0]]}
                isEmpty={false}
                filter="done"
            />,
        )
        expect(screen.getByText(/no tasks match/i)).toBeInTheDocument()
    })

    it('shows guest read-only banner for guest', () => {
        render(<MobileTaskList {...defaultProps} isGuest={true} />)
        expect(screen.getByText(/viewing as a guest/i)).toBeInTheDocument()
    })

    it('hides "New task" button in empty state for guest', () => {
        render(<MobileTaskList {...defaultProps} tasks={[]} isEmpty={true} isGuest={true} />)
        expect(screen.queryByRole('button', { name: /new task/i })).not.toBeInTheDocument()
    })

    it('calls onTaskTap when a task row is clicked', async () => {
        const onTaskTap = vi.fn()
        render(<MobileTaskList {...defaultProps} onTaskTap={onTaskTap} />)
        await userEvent.click(screen.getByText('Write tests'))
        expect(onTaskTap).toHaveBeenCalledWith('task-1')
    })
})

describe('MobileTaskDetail', () => {
    const defaultProps = {
        task: makeDetail(),
        projectName: 'My Project',
        workspaceId: 'ws-1',
        projectId: 'proj-1',
        isGuest: false,
        canEdit: true,
        onBack: vi.fn(),
        onEdit: vi.fn(),
        onDelete: vi.fn(),
    }

    it('renders task title', () => {
        render(<MobileTaskDetail {...defaultProps} />)
        expect(screen.getAllByText('Write tests').length).toBeGreaterThan(0)
    })

    it('shows edit and delete in ⋯ menu for owner/creator', async () => {
        render(<MobileTaskDetail {...defaultProps} canEdit={true} />)
        await userEvent.click(screen.getByRole('button', { name: /task actions/i }))
        expect(screen.getAllByText('Edit').length).toBeGreaterThan(0)
        expect(screen.getAllByText('Delete').length).toBeGreaterThan(0)
    })

    it('hides ⋯ menu for guest (canEdit=false)', () => {
        render(<MobileTaskDetail {...defaultProps} canEdit={false} />)
        expect(screen.queryByRole('button', { name: /task actions/i })).not.toBeInTheDocument()
    })

    it('hides action row buttons for guest (isGuest=true)', () => {
        render(<MobileTaskDetail {...defaultProps} isGuest={true} canEdit={false} />)
        expect(screen.queryByText('In progress')).not.toBeInTheDocument()
        expect(screen.queryByRole('button', { name: /edit/i })).not.toBeInTheDocument()
    })

    it('calls onBack when back button is clicked', async () => {
        const onBack = vi.fn()
        render(<MobileTaskDetail {...defaultProps} onBack={onBack} />)
        await userEvent.click(screen.getByRole('button', { name: /back/i }))
        expect(onBack).toHaveBeenCalledTimes(1)
    })

    it('shows project name in detail card', () => {
        render(<MobileTaskDetail {...defaultProps} projectName="Alpha" />)
        expect(screen.getByText('Alpha')).toBeInTheDocument()
    })

    it('shows description when present', () => {
        render(
            <MobileTaskDetail
                {...defaultProps}
                task={makeDetail({ description: 'Some details here' })}
            />,
        )
        expect(screen.getByText('Some details here')).toBeInTheDocument()
    })
})
