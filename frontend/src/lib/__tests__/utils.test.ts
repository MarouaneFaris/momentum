import { cn } from '@/lib/utils'

describe('cn', () => {
    it('merges class names', () => {
        expect(cn('a', 'b')).toBe('a b')
    })

    it('resolves tailwind conflicts (last wins)', () => {
        expect(cn('px-2', 'px-4')).toBe('px-4')
    })

    it('drops falsy values', () => {
        expect(cn('a', false && 'b', undefined)).toBe('a')
    })
})
