import type { ErrorCode } from './ErrorCode'

export default class ApiError extends Error {
    public status: number
    public code: ErrorCode | null

    constructor(status: number, message: string, code: ErrorCode | null = null) {
        super(message)
        this.status = status
        this.code = code
    }
}
