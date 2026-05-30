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

    it('renders wordmark text', () => {
        render(<MomentumLogo size="sm" />)
        expect(screen.getByText('m')).toBeInTheDocument()
        expect(screen.getByText('omentum')).toBeInTheDocument()
    })

    it('renders 3 stripes', () => {
        const { getAllByTestId } = render(<MomentumLogo size="sm" />)
        expect(getAllByTestId('logo-stripe')).toHaveLength(3)
    })

    it('applies sm dimensions to stripes', () => {
        const { getAllByTestId } = render(<MomentumLogo size="sm" />)
        const stripes = getAllByTestId('logo-stripe')
        stripes.forEach((stripe) => {
            expect(stripe.className).toMatch(/w-\[14px\]/)
            expect(stripe.className).toMatch(/h-\[3px\]/)
        })
    })

    it('applies lg dimensions to stripes', () => {
        const { getAllByTestId } = render(<MomentumLogo size="lg" />)
        const stripes = getAllByTestId('logo-stripe')
        stripes.forEach((stripe) => {
            expect(stripe.className).toMatch(/w-\[28px\]/)
            expect(stripe.className).toMatch(/h-\[5px\]/)
        })
    })

    it('applies correct opacity classes to stripes', () => {
        const { getAllByTestId } = render(<MomentumLogo size="sm" />)
        const stripes = getAllByTestId('logo-stripe')
        expect(stripes[0].className).toMatch(/opacity-25/)
        expect(stripes[1].className).toMatch(/opacity-55/)
        expect(stripes[2].className).not.toMatch(/opacity-/)
    })

    it('applies -12deg skew to the mark', () => {
        const { container } = render(<MomentumLogo size="sm" />)
        const mark = container.querySelector('[style*="skewX"]') as HTMLElement
        expect(mark).not.toBeNull()
        expect(mark.style.transform).toBe('skewX(-12deg)')
    })

    it('applies text-primary class to first letter', () => {
        render(<MomentumLogo size="sm" />)
        const firstLetter = screen.getByText('m')
        expect(firstLetter.className).toMatch(/text-primary/)
    })

    it('applies text-foreground class to remaining letters', () => {
        render(<MomentumLogo size="sm" />)
        const rest = screen.getByText('omentum')
        expect(rest.className).toMatch(/text-foreground/)
    })

    it('applies lg text classes for lg size', () => {
        render(<MomentumLogo size="lg" />)
        const wordmark = screen.getByText('m').parentElement as HTMLElement
        expect(wordmark.className).toMatch(/text-3xl/)
        expect(wordmark.className).toMatch(/tracking-tighter/)
    })

    it('applies sm text classes for sm size', () => {
        render(<MomentumLogo size="sm" />)
        const wordmark = screen.getByText('m').parentElement as HTMLElement
        expect(wordmark.className).toMatch(/text-sm/)
        expect(wordmark.className).toMatch(/tracking-tight/)
    })
})
