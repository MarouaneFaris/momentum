import { workspaceStorage } from './workspaceStorage'

describe('workspaceStorage', () => {
    beforeEach(() => {
        localStorage.clear()
    })

    it('read returns null when key is not set', () => {
        expect(workspaceStorage.read()).toBeNull()
    })

    it('write stores id and read returns it', () => {
        workspaceStorage.write('abc-123')
        expect(workspaceStorage.read()).toBe('abc-123')
    })

    it('write overwrites previous value', () => {
        workspaceStorage.write('first')
        workspaceStorage.write('second')
        expect(workspaceStorage.read()).toBe('second')
    })
})
