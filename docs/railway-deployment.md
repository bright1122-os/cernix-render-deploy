# Railway Deployment

This guide prepares CERNIX for a Railway deployment backed by PostgreSQL.

## 1. Push To GitHub

Commit the Railway deployment files and push the repository to GitHub:

```bash
git add railway.json .env.example docs/railway-deployment.md
git commit -m "Prepare Railway deployment"
git push
```

## 2. Create Railway Project

1. Open Railway and create a new project.
2. Choose **Deploy from GitHub repo**.
3. Select the CERNIX repository.

## 3. Add PostgreSQL

Add a PostgreSQL service in the same Railway project. Railway exposes the database URL as `Postgres.DATABASE_URL`.

## 4. Required Variables

Set these variables on the Laravel service:

```env
APP_NAME=CERNIX
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=

LOG_CHANNEL=stderr
LOG_LEVEL=info

DB_CONNECTION=pgsql
DB_URL=${{Postgres.DATABASE_URL}}

CACHE_STORE=database
SESSION_DRIVER=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=public

CERNIX_DEMO_MODE=false

REMITA_MERCHANT_ID=
REMITA_API_KEY=
REMITA_SERVICE_TYPE_ID=
REMITA_BASE_URL=
REMITA_PUBLIC_KEY=
REMITA_SECRET_KEY=

CERNIX_HMAC_KEY=
CERNIX_ENCRYPTION_KEY=
APP_JWT_SECRET=
JWT_SECRET=
```

Generate the Laravel app key locally:

```bash
php artisan key:generate --show
```

Paste the generated value into Railway as `APP_KEY`.

## 5. Demo Mode

Production deploys should normally keep:

```env
CERNIX_DEMO_MODE=false
```

For a public demo where TEST- RRR values should work, set:

```env
CERNIX_DEMO_MODE=true
```

TEST- values remain demo-only. With `APP_ENV=production` and `CERNIX_DEMO_MODE=false`, TEST- registration values are rejected.

## 6. Public Domain

Railway services are private until a domain is generated.

1. Open the Laravel service.
2. Go to **Networking**.
3. Generate a public domain.
4. Set `APP_URL` to the generated HTTPS URL.
5. Redeploy if the app was already built with a different URL.

## 7. Build And Deploy

`railway.json` uses Nixpacks and runs:

```bash
composer install --no-dev --optimize-autoloader && npm ci && npm run build
```

Before each deploy, Railway runs:

```bash
php artisan migrate --force && php artisan db:seed --force && php artisan config:cache && php artisan route:cache && php artisan view:cache
```

The app starts with:

```bash
php artisan serve --host=0.0.0.0 --port=$PORT
```

## 8. Post-Deploy Smoke Test

After Railway finishes deploying, open the public HTTPS URL and test:

- `/`
- `/student/register`
- `/admin/login`
- `/admin/dashboard`
- `/admin/settings`
- `/examiner/login`
- `/examiner/dashboard`
- Student registration with `TEST-DEMO` if `CERNIX_DEMO_MODE=true`
- QR generation from the student dashboard
- Examiner scanner page renders
- Admin/Super Admin cannot enter the Examiner portal
- Examiner cannot enter the Admin portal

## 9. Production Notes

- Keep `APP_DEBUG=false`.
- Keep logs on `stderr` through `LOG_CHANNEL=stderr`.
- Use PostgreSQL through `DB_CONNECTION=pgsql` and `DB_URL=${{Postgres.DATABASE_URL}}`.
- Do not store real Remita or crypto secrets in the repository.
- Demo passport images are committed under `public/demo-passports/`.
- Project media images are documentation-only and are not used as student identity photos.
