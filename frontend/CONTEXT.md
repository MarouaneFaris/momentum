# Frontend Context

React 19 + TypeScript + Vite, port 3000.

## Stack

React 19, React Router 7, TanStack Query 5, shadcn/ui, Tailwind 4, react-hook-form + zod, next-themes, sonner.

## Folder structure (`src/`)

```
src/
├── main.tsx                   # entry point only — createRoot + render
├── App.tsx                    # providers + router + routes
├── index.css
├── assets/
├── components/
│   └── ui/                    # shadcn primitives only
├── features/                  # one folder per domain, self-contained
│   └── <feature>/
│       ├── components/
│       ├── hooks/
│       ├── queries.ts         # TanStack Query defs
│       └── types.ts
├── layouts/                   # route shells (AuthLayout, AppLayout, etc.)
├── pages/                     # thin route components — compose features, no logic
├── contexts/                  # app-wide React contexts (AuthContext, etc.)
├── lib/
│   ├── api.ts                 # fetch client
│   ├── queryClient.ts         # TanStack QueryClient instance
│   └── utils.ts               # cn() and misc helpers
└── types/                     # shared global types
```

## Rules

- New domain = new `features/<name>/` folder. Co-locate components, hooks, queries, types inside it.
- No cross-feature imports. Shared code gets hoisted to `lib/` or `types/`.
- Context rule: if two+ unrelated features import a context, it belongs in `contexts/`, not in a feature.
- `pages/` components are thin — compose features and layouts only, no business logic.

## Testing

- Runner: Vitest + jsdom (`vitest.config.ts`)
- Libraries: `@testing-library/react`, `@testing-library/user-event`, `@testing-library/jest-dom`
- Setup file: `src/test/setup.ts` — imports jest-dom matchers
- Test files co-locate next to the file under test (`Foo.test.tsx` beside `Foo.tsx`)
- Mock external deps (router, API calls, hooks) at module level with `vi.mock()`
- Use `it` over `test` (enforced by ESLint)
- Run: `make front-test` · `npm run test:run` (CI / one-shot) · `npm test` (watch mode)

## Data fetching

All data-fetching queries **must** use `useSettledQuery` from `src/lib/useSettledQuery.ts` instead of `useQuery` directly. By default it gates on `isAuthenticated`, so no query fires before identity is confirmed and no request leaks when the user is logged out.

```typescript
import { useSettledQuery } from '@/lib/useSettledQuery'

// default: fires only when authenticated
export const useFoo = () =>
    useSettledQuery({ queryKey: ['foo'], queryFn: () => api.get('/foo') })

// requireAuth: false — fires once /api/me has settled, regardless of auth state
// Use only for queries that must work for unauthenticated users (e.g. dev tooling)
export const usePublicFoo = () =>
    useSettledQuery({ queryKey: ['foo'], queryFn: () => api.get('/foo'), requireAuth: false })
```

The sole exception is `useAuth` in `features/auth/queries.ts` — it uses raw `useQuery` because it is the auth driver itself and cannot gate on its own result.

## Workspace-scoped API calls

All workspace-scoped API calls **must** use `useWorkspaceApi()` from `src/lib/useWorkspaceApi.ts`. The hook reads `workspaceId` from the URL params and returns pre-bound API methods that prepend `/workspaces/{id}` to every path. It throws if called outside a `/workspaces/:id/` route.

```typescript
const { workspaceId, get, post, patch, delete: del } = useWorkspaceApi()
```

### Query key convention

All workspace-scoped TanStack Query keys **must** include `workspaceId` as the second element to isolate cache per workspace:

```typescript
// correct
queryKey: ['workspaces', workspaceId, 'projects']
queryKey: ['workspaces', workspaceId, 'projects', projectId, 'tasks']

// wrong — shared cache across workspaces
queryKey: ['projects']
```

## Routing

Workspace identity is always URL-encoded — not stored in React context or hidden state. This keeps links shareable and deep-linkable, consistent with the API's explicit workspace scoping.

### Route tree

```
/                                   → smart landing: redirect to last-visited workspace (localStorage)
                                      || first result from GET /api/workspaces
                                      || render empty state inline if no workspaces
/workspaces/:id/                    → redirect to /workspaces/:id/dashboard
/workspaces/:id/dashboard           → workspace dashboard
/workspaces/:id/settings            → workspace settings (rename + delete zone for owner; read-only for member/guest)
```

### Workspace switching

Sidebar dropdown at top of AppLayout sidebar:

- Shows current workspace name
- Click → popover listing all user workspaces with their role
- Select → navigate to `/workspaces/{id}/dashboard`
- "Create workspace" trigger at bottom of popover → opens inline modal (single `name` field)
- After creation → navigate to new workspace's dashboard

### Last-visited workspace

localStorage key: `lastVisitedWorkspaceId`. Written on every workspace navigation. Read on post-login redirect. If key is missing or the workspace is no longer accessible, fall back to first result from `GET /api/workspaces`.

### Delete confirmation

Workspace deletion requires typing the workspace name to confirm (type-to-confirm pattern). Delete button is disabled until input matches exactly. After deletion: redirect to next available workspace, or render empty state on `/` if none remain.
