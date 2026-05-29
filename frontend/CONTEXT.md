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
- Test files co-locate in `__tests__/` next to the file under test
- Mock external deps (router, API calls, hooks) at module level with `vi.mock()`
- Run: `npm run test:run` (CI / one-shot) · `npm test` (watch mode)
