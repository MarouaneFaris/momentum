export default class ApiError extends Error {
    constructor(
        public code: string,
        public httpStatus: number,
        public devMessage: string,
        public context: Record<string, unknown> = {},
    ) {
        super(devMessage)
    }
}
