# Railway production provisioning runbook

Standing up the production stack on Railway from an empty project.

**References:** [PRD #308](https://github.com/MarouaneFaris/momentum/issues/308) · [ADR-013](../adr/013-production-deployment-architecture-on-railway.md)

---

## Prerequisites

- Railway account with a team or personal workspace
- Railway CLI installed: `npm install -g @railway/cli`
- GitHub repo admin access (to add secrets and variables)
- `openssl` available locally for secret generation

---

## 1. Create the Railway project

1. Open [railway.app](https://railway.app) → **New Project**.
2. Name it `momentum`.
3. Leave it empty — services are added in the next steps.

---

## 2. Provision services

### 2.1 `app` service

1. Inside the project → **New Service** → **GitHub Repo**.
2. Connect the `MarouaneFaris/momentum` repository.
3. Railway reads build configuration from `railway.toml` at repo root — no dashboard fields to fill:

   ```toml
   [build]
   dockerfilePath = "docker/Dockerfile"
   dockerBuildTarget = "frankenphp_prod"
   ```

   This file is committed to the repo, so the build target and Dockerfile path are set automatically.

### 2.2 `mariadb` service

1. **New Service** → **Docker Image**.
2. Image: `mariadb:lts`
3. Rename the service to `mariadb`.

### 2.3 `redis` service

1. **New Service** → **Database** → **Redis** (Railway-managed plugin).
2. Railway auto-injects `REDIS_URL` into any service that references it — see §4 for wiring.

### 2.4 `worker` service

The Messenger worker that delivers async emails (ADR-015). Same repo and same
Docker image as `app` — only the start command differs.

1. **New Service** → **GitHub Repo** → connect `MarouaneFaris/momentum` (same repo as `app`).
2. Rename the service to `worker`.
3. **Settings** → **Config-as-code** → set **Config Path** to `railway.worker.toml`.

   This file (committed at repo root) reuses `docker/Dockerfile` and overrides
   the start command to consume `async_priority_high async` (the command is
   wrapped in `sh -c` so the image entrypoint skips its migration block — see the
   file's comments). It declares no healthcheck (the worker is not HTTP) and no
   pre-deploy command (migrations run on `app` only).

> The worker consumes **both** queues — emails route to `async_priority_high`
> (see `messenger.yaml` routing). Consuming `async` alone would never deliver email.

> The image still contains the frontend build (shared Dockerfile) — harmless dead
> weight for the worker. Eliminating it would require a single-artifact registry
> deploy; deferred (see #548 discussion).

---

## 3. Attach persistent storage to `mariadb`

1. Select the `mariadb` service → **Volumes** tab → **New Volume**.
2. Mount path: `/var/lib/mysql`

> The `app` service is stateless — no volume needed.

---

## 4. Configure environment variables

### 4.1 Shared variable group — DB credentials

Create a shared variable group so credentials are defined once and referenced by both `mariadb` and `app`.

1. Project settings → **Shared Variables** → **New Group**, name it `db-credentials`.
2. Add:

   | Variable           | Value                                |
   | ------------------ | ------------------------------------ |
   | `MARIADB_USER`     | `momentum`                           |
   | `MARIADB_PASSWORD` | _(generate: `openssl rand -hex 32`)_ |
   | `MARIADB_DATABASE` | `momentum`                           |

3. Reference this group from both the `mariadb` and `app` services.

### 4.2 `mariadb` service variables

Add these directly on the `mariadb` service (in addition to the shared group above):

| Variable                | Value                                |
| ----------------------- | ------------------------------------ |
| `MARIADB_ROOT_PASSWORD` | _(generate: `openssl rand -hex 32`)_ |

### 4.3 `app` service variables

Set all of the following on the `app` service.

**Database URL** — uses the shared group and Railway's internal hostname:

```
DATABASE_URL=mysql://${{MARIADB_USER}}:${{MARIADB_PASSWORD}}@${{mariadb.RAILWAY_PRIVATE_DOMAIN}}:3306/${{MARIADB_DATABASE}}?serverVersion=mariadb-11.8.6&charset=utf8mb4
```

**Redis** — reference the managed Redis plugin:

```
REDIS_URL=${{Redis.REDIS_URL}}
```

**Symfony / FrankenPHP:**

| Variable                  | Value                                |
| ------------------------- | ------------------------------------ |
| `APP_ENV`                 | `prod`                               |
| `APP_SECRET`              | _(generate: `openssl rand -hex 32`)_ |
| `SYMFONY_TRUSTED_PROXIES` | `REMOTE_ADDR`                        |
| `MESSENGER_CONSUMER_ID`   | `web`                                |

> `FRONTEND_URL` is **not** set on `app` — the only code that reads it is the email
> handler, which runs on the `worker` (§4.4). If you add a synchronous, app-side
> consumer of `FRONTEND_URL` later, set it here too.

> `MESSENGER_CONSUMER_ID` has no committed default in `api/.env` beyond a generic
> fallback — set it explicitly per service so the Redis stream consumer group
> identifies each process distinctly. The `app` service only *produces* messages,
> but the env var must still resolve when the transport is instantiated to dispatch.

> **`SERVER_NAME` must be set before the first deploy.** Railway injects `PORT` at runtime; Caddy binds to whatever `SERVER_NAME` is set to. An empty or missing value produces a bare server block that Caddy rejects — FrankenPHP crashes on startup and all healthchecks fail. `:${{PORT}}` (Railway's reference syntax) resolves to the correct port automatically.

> `SYMFONY_TRUSTED_PROXIES=REMOTE_ADDR` is required so the IP-based rate limiter (ADR-007) sees real client IPs, not Railway's internal hop. Safe here because Railway is the sole network layer in front of Caddy. See ADR-013 §Decision 4.

**Caddy env-injected directives:**

| Variable                     | Value                                        |
| ---------------------------- | -------------------------------------------- |
| `CADDY_SPA_FALLBACK`         | `try_files {path} /index.html`               |
| `CADDY_SECURITY_HEADERS`     | _(multiline — paste exactly as shown below)_ |
| `MERCURE_EXTRA_DIRECTIVES`   | _(empty string)_                             |
| `MERCURE_PUBLISHER_JWT_KEY`  | _(generate: `openssl rand -hex 32`)_ — signs PHP→hub publish JWTs      |
| `MERCURE_SUBSCRIBER_JWT_KEY` | _(generate: `openssl rand -hex 32`)_ — signs browser subscriber JWTs   |
| `MERCURE_URL`                | `http://localhost:${{PORT}}/.well-known/mercure`               |
| `MERCURE_PUBLIC_URL`         | `https://${{RAILWAY_PUBLIC_DOMAIN}}/.well-known/mercure`       |
| `VITE_MERCURE_PUBLIC_URL`    | `/.well-known/mercure`                                         |

`CADDY_SECURITY_HEADERS` value (paste as-is — Caddy reads this as a block of directives):

```
header Strict-Transport-Security "max-age=31536000; includeSubDomains"
header X-Content-Type-Options "nosniff"
header Referrer-Policy "strict-origin-when-cross-origin"
header X-Frame-Options "DENY"
```

**Email:** not configured on `app`. Email is dispatched asynchronously — the `app`
service only enqueues the message; the **worker** builds and sends it. Set
`MAILER_DSN` / `MAILER_SENDER` on the `worker` service instead (§4.4).

> No `.env.prod` file is committed to the repo. All production values live exclusively in Railway. See [ADR-010](../adr/010-env-file-architecture.md).

### 4.4 `worker` service variables

The worker runs the same code as `app` and shares the same backing services. It is
the service that **sends** email: it consumes the queue, builds the verification
link, and delivers via the mail transport — so the mail and `FRONTEND_URL`
variables live here, not on `app`. Reference the `db-credentials` shared group (as
`app` does) and set:

| Variable                | Value                                                                                                          |
| ----------------------- | -------------------------------------------------------------------------------------------------------------- |
| `APP_ENV`               | `prod`                                                                                                         |
| `APP_SECRET`            | _(same value as `app`)_                                                                                        |
| `MESSENGER_CONSUMER_ID` | `worker`                                                                                                       |
| `DATABASE_URL`          | `mysql://${{MARIADB_USER}}:${{MARIADB_PASSWORD}}@${{mariadb.RAILWAY_PRIVATE_DOMAIN}}:3306/${{MARIADB_DATABASE}}?serverVersion=mariadb-11.8.6&charset=utf8mb4` |
| `REDIS_URL`             | `${{Redis.REDIS_URL}}`                                                                                         |
| `FRONTEND_URL`          | `https://${{app.RAILWAY_PUBLIC_DOMAIN}}` — built into the verification link by the handler (runs here)          |
| `MAILER_DSN`            | `resend+api://YOUR_RESEND_API_KEY@default`                                                                     |
| `MAILER_SENDER`         | `no-reply@yourdomain.com` — a verified sender on your Resend domain                                            |

> Before setting `MAILER_DSN`: create a Resend account at [resend.com](https://resend.com),
> verify your sending domain, then generate an API key. Replace `YOUR_RESEND_API_KEY`
> with it. See [ADR-015](../adr/015-async-email-dispatch.md) for provider rationale.
>
> `FRONTEND_URL` must point at the public web app. The worker has no public domain,
> so reference the `app` service's domain with `${{app.RAILWAY_PUBLIC_DOMAIN}}`
> (cross-service reference; assumes the web service is named `app`). In the
> same-origin deploy that domain is both the API and the SPA.

> `MESSENGER_CONSUMER_ID` is the worker's Redis stream consumer name within the
> `symfony` group. A single stable value (`worker`) is correct for one replica.
> **Not scaling-safe:** if you raise the worker replica count, each replica must
> get a distinct consumer name — otherwise they share a pending list and
> double-process messages. Railway env vars are shared across a service's replicas,
> so multi-replica would need a per-process id source (Railway does not inject a
> reliable per-replica variable). Keep the worker at one replica unless you revisit
> this.

> `DATABASE_URL` is required even though the worker doesn't serve HTTP: the `failed`
> transport is Doctrine-backed (MariaDB), so the worker writes dead-lettered messages
> there. `REDIS_URL` is the async transport. No Caddy/Mercure or `VITE_*` build
> variables are needed — the worker never serves the web app.

---

## 5. Pre-deploy command and healthcheck

Both are declared in `railway.toml` (committed to repo) — no dashboard configuration needed:

```toml
[deploy]
healthcheckPath = "/api/health"
preDeployCommand = ["php bin/console doctrine:migrations:migrate --no-interaction"]
```

- **Pre-deploy:** a failed migration aborts the deploy; previous release keeps serving. See ADR-013 §Decision 3.
- **Healthcheck:** requires [#311](https://github.com/MarouaneFaris/momentum/issues/311) to land. Railway gates deploy promotion on a 200 response from `/api/health`.

---

## 6. Networking

- **`app`**: expose publicly — Railway auto-generates a subdomain. Enable in **Settings** → **Networking** → **Public Networking** → **Generate Domain**.
- **`mariadb`**: leave private — no public networking, internal Railway DNS only.
- **`redis`**: leave private — Railway-managed plugins are private by default.

---

## 7. CI/CD secrets and variables

After the first successful manual deploy, wire up GitHub Actions for continuous deployment.

### 7.1 Railway project token

1. Railway project → **Settings** → **Tokens** → **New Token**.
2. Add to GitHub repo as a **secret** named `RAILWAY_TOKEN`.

### 7.2 App service ID

1. `app` service → **Settings** → copy the **Service ID**.
2. Add to GitHub repo as a **variable** named `RAILWAY_APP_SERVICE_ID`.

### 7.3 Health URL

1. After the first deploy, copy the public subdomain URL (e.g. `https://momentum-prod.up.railway.app`).
2. Add to GitHub repo as a **variable** named `API_HEALTH_URL` with value `https://<subdomain>/api/health`.

### 7.4 Worker service ID

1. `worker` service → **Settings** → copy the **Service ID**.
2. Add to GitHub repo as a **variable** named `RAILWAY_WORKER_SERVICE_ID`.

The CI workflow (`ci.yaml`) consumes these — `RAILWAY_TOKEN` as a secret and `RAILWAY_APP_SERVICE_ID` / `RAILWAY_WORKER_SERVICE_ID` / `API_HEALTH_URL` as `vars.*`. The `deploy` job builds and ships both the `app` and `worker` services from the same tag.

---

## 8. Smoke test after first deploy

Run these checks in order after the first successful deploy.

### 8.1 Health endpoint

```bash
curl -i https://<subdomain>/api/health
```

Expected: `HTTP/2 200` with a JSON body confirming DB and Redis connectivity.

### 8.2 Auth cookie flags

```bash
# Register
curl -i -X POST https://<subdomain>/api/register \
  -H 'Content-Type: application/json' \
  -d '{"email":"smoke@example.com","password":"SmokeTest1!"}'

# Log in
curl -i -c cookies.txt -X POST https://<subdomain>/api/login \
  -H 'Content-Type: application/json' \
  -d '{"email":"smoke@example.com","password":"SmokeTest1!"}'
```

Inspect `cookies.txt` — the auth cookie must carry `HttpOnly`, `Secure`, and `SameSite=Strict` flags.

### 8.3 SPA deep-link reload

Open `https://<subdomain>/workspaces/<any-uuid>/dashboard` directly in a browser (paste into address bar to force a full navigation). The SPA shell must render, not a 404.

### 8.4 Security headers

```bash
curl -si https://<subdomain>/ | grep -iE 'strict-transport|x-content-type|referrer-policy|x-frame'
```

Expected headers:

```
strict-transport-security: max-age=31536000; includeSubDomains
x-content-type-options: nosniff
referrer-policy: strict-origin-when-cross-origin
x-frame-options: DENY
```

### 8.5 Worker delivers email

The registration in §8.2 dispatches a verification email to `async_priority_high`.

1. Open the `worker` service → **Deployments** → **Logs**. Confirm the process is
   running `messenger:consume` (no crash loop).
2. After registering, the logs should show the message being received and handled
   (a `Resend` delivery, per `MAILER_DSN`).
3. Check the Resend dashboard for the sent message.

If nothing is consumed: verify the worker's `REDIS_URL` matches `app`'s (same
instance) and that `MESSENGER_CONSUMER_ID` is set on both services.
