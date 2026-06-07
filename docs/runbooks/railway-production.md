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

> **`SERVER_NAME` must be set before the first deploy.** Railway injects `PORT` at runtime; Caddy binds to whatever `SERVER_NAME` is set to. An empty or missing value produces a bare server block that Caddy rejects — FrankenPHP crashes on startup and all healthchecks fail. `:${{PORT}}` (Railway's reference syntax) resolves to the correct port automatically.

> `SYMFONY_TRUSTED_PROXIES=REMOTE_ADDR` is required so the IP-based rate limiter (ADR-007) sees real client IPs, not Railway's internal hop. Safe here because Railway is the sole network layer in front of Caddy. See ADR-013 §Decision 4.

**Caddy env-injected directives:**

| Variable                     | Value                                        |
| ---------------------------- | -------------------------------------------- |
| `CADDY_SPA_FALLBACK`         | `try_files {path} /index.html`               |
| `CADDY_SECURITY_HEADERS`     | _(multiline — paste exactly as shown below)_ |
| `MERCURE_EXTRA_DIRECTIVES`   | _(empty string)_                             |
| `CADDY_MERCURE_JWT_SECRET`   | _(generate: `openssl rand -hex 32`)_                           |
| `MERCURE_PUBLISHER_JWT_KEY`  | _(same value as `CADDY_MERCURE_JWT_SECRET`)_                   |
| `MERCURE_SUBSCRIBER_JWT_KEY` | _(same value as `CADDY_MERCURE_JWT_SECRET`)_                   |
| `MERCURE_JWT_SECRET`         | _(same value as `CADDY_MERCURE_JWT_SECRET`)_                   |
| `MERCURE_URL`                | `http://localhost:${{PORT}}/.well-known/mercure`               |
| `MERCURE_PUBLIC_URL`         | `https://${{RAILWAY_PUBLIC_DOMAIN}}/.well-known/mercure`       |

`CADDY_SECURITY_HEADERS` value (paste as-is — Caddy reads this as a block of directives):

```
header Strict-Transport-Security "max-age=31536000; includeSubDomains"
header X-Content-Type-Options "nosniff"
header Referrer-Policy "strict-origin-when-cross-origin"
header X-Frame-Options "DENY"
```

> No `.env.prod` file is committed to the repo. All production values live exclusively in Railway. See [ADR-010](../adr/010-env-file-architecture.md).

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

The CI workflow (`ci.yml`) already consumes these — `RAILWAY_TOKEN` as a secret and both `RAILWAY_APP_SERVICE_ID` / `API_HEALTH_URL` as `vars.*`.

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
