import { copyFor } from '@/lib/errorCopy'

describe('copyFor', () => {
    it('returns copy for known code', () => {
        expect(copyFor('AUTH_INVALID_CREDENTIALS')).toBe('Email or password is incorrect.')
    })

    it('returns generic message for unknown code', () => {
        expect(copyFor('TOTALLY_UNKNOWN_CODE')).toBe('Something went wrong. Please try again.')
    })
})
