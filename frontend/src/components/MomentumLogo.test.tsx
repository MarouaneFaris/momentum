import { render, screen } from '@testing-library/react'
import { MomentumLogo } from '@/components/MomentumLogo'

describe('MomentumLogo', () => {
    it('renders sm variant without error', () => {
        render(<MomentumLogo size="sm" />)
        expect(screen.getByRole('img', { name: 'Momentum' })).toBeInTheDocument()
    })

    it('renders lg variant without error', () => {
        render(<MomentumLogo size="lg" />)
        expect(screen.getByRole('img', { name: 'Momentum' })).toBeInTheDocument()
    })

    it('sm renders icon-only SVG (no text)', () => {
        const { container } = render(<MomentumLogo size="sm" />)
        expect(container.querySelector('text')).toBeNull()
    })

    it('lg renders wordmark text', () => {
        render(<MomentumLogo size="lg" />)
        expect(screen.getByText(/momentum/i)).toBeInTheDocument()
    })

    it('sm renders 3 stripes', () => {
        const { container } = render(<MomentumLogo size="sm" />)
        expect(container.querySelectorAll('rect')).toHaveLength(3)
    })

    it('lg renders 3 stripes', () => {
        const { container } = render(<MomentumLogo size="lg" />)
        expect(container.querySelectorAll('rect')).toHaveLength(3)
    })

    it('sm applies h-5 class', () => {
        render(<MomentumLogo size="sm" />)
        expect(screen.getByRole('img', { name: 'Momentum' }).className).toMatch(/h-5/)
    })

    it('lg applies h-10 class', () => {
        render(<MomentumLogo size="lg" />)
        expect(screen.getByRole('img', { name: 'Momentum' }).className).toMatch(/h-10/)
    })
})
