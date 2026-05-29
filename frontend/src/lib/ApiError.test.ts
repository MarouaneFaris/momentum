import ApiError from '@/lib/ApiError'

describe('ApiError', () => {
    it('sets status and message', () => {
        const error = new ApiError(401, 'Unauthorized')
        expect(error.status).toBe(401)
        expect(error.message).toBe('Unauthorized')
    })

    it('is an instance of Error', () => {
        expect(new ApiError(500, 'Internal')).toBeInstanceOf(Error)
    })
})
