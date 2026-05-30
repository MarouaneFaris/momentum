import { render, screen } from '@testing-library/react'
import { MomentumLogo } from '@/components/MomentumLogo'

describe('MomentumLogo', () => {
    it('renders sm variant without error', () => {
        const { container } = render(<MomentumLogo size="sm" />)
        expect(container.firstChild).toBeInTheDocument()
    })

    it('renders lg variant without error', () => {
        const { container } = render(<MomentumLogo size="lg" />)
        expect(container.firstChild).toBeInTheDocument()
    })

    it('renders two img elements for light/dark switching', () => {
        render(<MomentumLogo size="sm" />)
        const imgs = screen.getAllByRole('img', { name: 'Momentum' })
        expect(imgs).toHaveLength(2)
    })

    it('light image uses light SVG src', () => {
        render(<MomentumLogo size="sm" />)
        const imgs = screen.getAllByRole('img', { name: 'Momentum' })
        expect(imgs[0]).toHaveAttribute('src', '/logo-lockup-light.svg')
    })

    it('dark image uses dark SVG src', () => {
        render(<MomentumLogo size="sm" />)
        const imgs = screen.getAllByRole('img', { name: 'Momentum' })
        expect(imgs[1]).toHaveAttribute('src', '/logo-lockup-dark.svg')
    })

    it('sm variant applies h-5 class', () => {
        render(<MomentumLogo size="sm" />)
        const imgs = screen.getAllByRole('img', { name: 'Momentum' })
        imgs.forEach((img) => expect(img.className).toMatch(/h-5/))
    })

    it('lg variant applies h-10 class', () => {
        render(<MomentumLogo size="lg" />)
        const imgs = screen.getAllByRole('img', { name: 'Momentum' })
        imgs.forEach((img) => expect(img.className).toMatch(/h-10/))
    })
})
