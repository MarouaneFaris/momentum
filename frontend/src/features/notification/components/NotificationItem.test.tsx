import { render, screen } from '@testing-library/react'
import { NotificationItem } from './NotificationItem'
import type { Notification } from '../types'

const base: Pick<Notification, 'id' | 'read_at' | 'created_at'> = {
    id: 'notif-1',
    read_at: null,
    created_at: new Date().toISOString(),
}

describe('NotificationItem', () => {
    it('renders task_assigned_to_you copy', () => {
        const n: Notification = {
            ...base,
            type: 'task_assigned_to_you',
            payload: { task_id: 't1', task_title: 'Fix the bug' },
        }
        render(<NotificationItem notification={n} />)
        expect(screen.getByText(/You were assigned/)).toBeInTheDocument()
        expect(screen.getByText('Fix the bug')).toBeInTheDocument()
    })

    it('renders task_assigned_member copy', () => {
        const n: Notification = {
            ...base,
            type: 'task_assigned_member',
            payload: { task_id: 't1', task_title: 'Fix the bug', assignee_name: 'Alice' },
        }
        render(<NotificationItem notification={n} />)
        expect(screen.getByText('Alice')).toBeInTheDocument()
        expect(screen.getByText(/was assigned/)).toBeInTheDocument()
    })

    it('renders task_status_changed_yours copy', () => {
        const n: Notification = {
            ...base,
            type: 'task_status_changed_yours',
            payload: { task_id: 't1', task_title: 'Deploy app', new_status: 'done' },
        }
        render(<NotificationItem notification={n} />)
        expect(screen.getByText('Deploy app')).toBeInTheDocument()
        expect(screen.getByText(/was marked/)).toBeInTheDocument()
        expect(screen.getByText('done')).toBeInTheDocument()
    })

    it('renders task_status_changed_member copy', () => {
        const n: Notification = {
            ...base,
            type: 'task_status_changed_member',
            payload: {
                task_id: 't1',
                task_title: 'Deploy app',
                new_status: 'in-progress',
                actor_name: 'Bob',
            },
        }
        render(<NotificationItem notification={n} />)
        expect(screen.getByText('Bob')).toBeInTheDocument()
        expect(screen.getByText(/updated/)).toBeInTheDocument()
        expect(screen.getByText('Deploy app')).toBeInTheDocument()
        expect(screen.getByText('in-progress')).toBeInTheDocument()
    })

    it('renders invitation_received copy', () => {
        const n: Notification = {
            ...base,
            type: 'invitation_received',
            payload: { workspace_name: 'Acme', role_name: 'member' },
        }
        render(<NotificationItem notification={n} />)
        expect(screen.getByText(/You were invited to/)).toBeInTheDocument()
        expect(screen.getByText('Acme')).toBeInTheDocument()
        expect(screen.getByText('member')).toBeInTheDocument()
    })

    it('renders invitation_accepted copy', () => {
        const n: Notification = {
            ...base,
            type: 'invitation_accepted',
            payload: { actor_name: 'Carol', workspace_name: 'Acme' },
        }
        render(<NotificationItem notification={n} />)
        expect(screen.getByText('Carol')).toBeInTheDocument()
        expect(screen.getByText(/accepted your invitation to/)).toBeInTheDocument()
    })

    it('renders invitation_declined copy', () => {
        const n: Notification = {
            ...base,
            type: 'invitation_declined',
            payload: { actor_name: 'Dan', workspace_name: 'Acme' },
        }
        render(<NotificationItem notification={n} />)
        expect(screen.getByText('Dan')).toBeInTheDocument()
        expect(screen.getByText(/declined your invitation to/)).toBeInTheDocument()
    })

    it('shows blue dot for unread', () => {
        const n: Notification = {
            ...base,
            type: 'invitation_received',
            payload: { workspace_name: 'Acme', role_name: 'member' },
        }
        render(<NotificationItem notification={n} />)
        expect(screen.getByLabelText('unread')).toBeInTheDocument()
    })

    it('shows muted dot for read', () => {
        const n: Notification = {
            ...base,
            read_at: new Date().toISOString(),
            type: 'invitation_received',
            payload: { workspace_name: 'Acme', role_name: 'member' },
        }
        render(<NotificationItem notification={n} />)
        expect(screen.getByLabelText('read')).toBeInTheDocument()
    })
})
