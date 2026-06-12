export default class ApiError extends Error {
    code: string
    httpStatus: number
    devMessage: string
    context: Record<string, unknown>

    constructor(
        code: string,
        httpStatus: number,
        devMessage: string,
        context: Record<string, unknown> = {},
    ) {
        super(devMessage)
        this.code = code
        this.httpStatus = httpStatus
        this.devMessage = devMessage
        this.context = context
    }
}
