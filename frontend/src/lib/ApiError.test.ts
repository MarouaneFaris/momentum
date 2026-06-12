import ApiError from '@/lib/ApiError'

describe('ApiError', () => {
    it('sets all fields', () => {
        const error = new ApiError('AUTH_INVALID_CREDENTIALS', 401, 'Invalid credentials', {
            field: 'email',
        })
        expect(error.code).toBe('AUTH_INVALID_CREDENTIALS')
        expect(error.httpStatus).toBe(401)
        expect(error.devMessage).toBe('Invalid credentials')
        expect(error.context).toEqual({ field: 'email' })
    })

    it('defaults context to empty object', () => {
        const error = new ApiError('UNKNOWN', 500, 'err')
        expect(error.context).toEqual({})
    })

    it('is an instance of Error', () => {
        expect(new ApiError('UNKNOWN', 500, 'err')).toBeInstanceOf(Error)
    })
})
