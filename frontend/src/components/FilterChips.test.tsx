import { fireEvent, render, screen } from '@testing-library/react'
import { describe, expect, it, vi } from 'vitest'
import { FilterChips } from './FilterChips'

const options = [
    { label: 'All', value: 'all' },
    { label: 'To do', value: 'todo' },
    { label: 'Done', value: 'done' },
]

describe('FilterChips', () => {
    it('renders all options', () => {
        render(<FilterChips options={options} value="all" onChange={vi.fn()} />)
        expect(screen.getByText('All')).toBeInTheDocument()
        expect(screen.getByText('To do')).toBeInTheDocument()
        expect(screen.getByText('Done')).toBeInTheDocument()
    })

    it('calls onChange with correct value on click', () => {
        const onChange = vi.fn()
        render(<FilterChips options={options} value="all" onChange={onChange} />)
        fireEvent.click(screen.getByText('To do'))
        expect(onChange).toHaveBeenCalledWith('todo')
    })

    it('active chip is visually identifiable', () => {
        render(<FilterChips options={options} value="todo" onChange={vi.fn()} />)
        const activeBtn = screen.getByText('To do').closest('button')
        expect(activeBtn?.className).toContain('text-primary')
    })
})
