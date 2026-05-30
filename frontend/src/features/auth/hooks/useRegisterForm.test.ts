import { renderHook, act } from '@testing-library/react'
import { useRegisterForm } from './useRegisterForm'

vi.mock('react-router', () => ({ useNavigate: () => vi.fn() }))
vi.mock('sonner', () => ({ toast: Object.assign(vi.fn(), { error: vi.fn() }) }))

const mockMutate = vi.fn()
vi.mock('../queries', () => ({
    useRegister: () => ({ mutate: mockMutate }),
}))

const makeChangeEvent = (name: string, value: string) =>
    ({ target: { name, value }, type: 'change' }) as unknown as React.ChangeEvent<HTMLInputElement>

describe('useRegisterForm', () => {
    beforeEach(() => {
        vi.clearAllMocks()
    })

    it('clears confirm error when password is updated to match confirm in onChange mode', async () => {
        const { result } = renderHook(() => useRegisterForm())

        await act(async () => {
            await result.current
                .register('confirm')
                .onChange(makeChangeEvent('confirm', 'StrongPassw0rd!'))
        })

        await act(async () => {
            await result.current
                .register('password')
                .onChange(makeChangeEvent('password', 'different'))
        })

        await act(async () => {
            await result.current
                .register('password')
                .onChange(makeChangeEvent('password', 'StrongPassw0rd!'))
        })

        expect(result.current.errors.confirm).toBeUndefined()
    })
})
