import { fireEvent, render, screen } from '@testing-library/react'
import { describe, expect, it, vi } from 'vitest'
import { BottomSheet } from './BottomSheet'

describe('BottomSheet', () => {
    it('renders children when open', () => {
        render(
            <BottomSheet open={true} onOpenChange={vi.fn()}>
                <p>Sheet content</p>
            </BottomSheet>,
        )
        expect(screen.getByText('Sheet content')).toBeInTheDocument()
    })

    it('does not render children when closed', () => {
        render(
            <BottomSheet open={false} onOpenChange={vi.fn()}>
                <p>Sheet content</p>
            </BottomSheet>,
        )
        expect(screen.queryByText('Sheet content')).not.toBeInTheDocument()
    })

    it('renders optional title', () => {
        render(
            <BottomSheet open={true} onOpenChange={vi.fn()} title="My Title">
                <p>content</p>
            </BottomSheet>,
        )
        expect(screen.getByText('My Title')).toBeInTheDocument()
    })

    it('calls onOpenChange when closed via keyboard', () => {
        const onOpenChange = vi.fn()
        render(
            <BottomSheet open={true} onOpenChange={onOpenChange}>
                <p>content</p>
            </BottomSheet>,
        )
        fireEvent.keyDown(document, { key: 'Escape' })
        expect(onOpenChange).toHaveBeenCalledWith(false)
    })
})
