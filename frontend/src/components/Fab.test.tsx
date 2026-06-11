import { render, screen } from '@testing-library/react'
import { Plus } from 'lucide-react'
import { describe, expect, it, vi } from 'vitest'
import { Fab } from './Fab'

describe('Fab', () => {
    it('renders for non-guest user', () => {
        render(<Fab onClick={vi.fn()} icon={Plus} />)
        expect(screen.getByRole('button')).toBeInTheDocument()
    })

    it('does not render when hidden=true (guest)', () => {
        render(<Fab onClick={vi.fn()} icon={Plus} hidden={true} />)
        expect(screen.queryByRole('button')).not.toBeInTheDocument()
    })
})
