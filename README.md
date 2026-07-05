# Competition Management System

Multi-tenant platform for running hackathon-style competitions — orgs manage events, participants submit work, judges score it, leaderboards rank the results.

Laravel 12 + Vue 3 + Inertia. Built incrementally, sprint by sprint.

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | Laravel 12, PHP 8.2+ |
| Frontend | Vue 3, TypeScript, Inertia.js |
| Styling | Tailwind CSS, shadcn-vue (radix-vue) |
| Database | MySQL 8 |
| Cache / Session / Queue | Redis |
| Testing | PHPUnit |
| Code Style | Laravel Pint, ESLint, Prettier |

## Architecture

- **Modular Monolith** — domain modules with consistent folder structure
- **Thin Controllers** — business logic in Services
- **Form Requests** — validation and authorization hooks
- **Policies** — authorization rules
- **Events + Jobs** — async side effects
- **Row-level multi-tenancy** — `organization_id` scoping (Sprint 1+)

## Prerequisites

- PHP 8.2+ with extensions: `pdo_mysql`, `redis`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`
- Composer 2.x
- Node.js 20+ and npm
- MySQL 8+
- Redis

Verify extensions:

```bash
php -m | grep -E 'pdo_mysql|redis'
```

Verify services:

```bash
redis-cli ping   # expect PONG
mysql -u root -e "SELECT 1"
```

## First-Time Setup

```bash
# 1. Install dependencies
composer install
npm install

# 2. Environment
cp .env.example .env
php artisan key:generate

# 3. Create database
mysql -u root -e "CREATE DATABASE IF NOT EXISTS competition_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 4. Run migrations
php artisan migrate

# 5. Link storage
php artisan storage:link
```

Update `.env` if your MySQL root user has a password:

```ini
DB_PASSWORD=your_password
```

## Development

Start all services (web server, queue worker, log tail, Vite):

```bash
composer dev
```

Or run separately:

```bash
php artisan serve          # http://localhost:8000
npm run dev                # Vite HMR
php artisan queue:work     # when testing async jobs
```

## Testing

```bash
php artisan test
```

Tests use SQLite in-memory (configured in `phpunit.xml`) — no MySQL required for the test suite.

## Code Quality

```bash
./vendor/bin/pint          # PHP formatting
npm run lint               # ESLint
npm run format             # Prettier
```

## Docker (Production Only)

Docker is prepared for future deployment. **Do not use Docker for local development.**

```bash
docker compose build
docker compose up -d
docker compose exec app php artisan migrate --force
docker compose exec app php artisan config:cache
```

See `docker-compose.yml` for service details.

## Project Structure

```
app/
├── Http/
│   ├── Controllers/{Module}/
│   └── Requests/{Module}/
├── Services/{Module}/
├── Policies/{Module}/
├── Jobs/{Module}/
├── Events/{Module}/
├── Listeners/{Module}/
├── Notifications/{Module}/
├── Models/
└── Enums/

resources/js/
├── pages/{module}/
├── components/
├── layouts/
└── types/
```

## Documentation

| Doc | What it covers |
|---|---|
| [PROJECT_RULES.md](PROJECT_RULES.md) | Coding standards for this repo |
| [docs/PRD.md](docs/PRD.md) | Product requirements |
| [docs/ROADMAP.md](docs/ROADMAP.md) | Sprint plan — updated when each sprint ships |
| [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) | System structure and request flow |
| [docs/DATABASE.md](docs/DATABASE.md) | Schema (migrations are source of truth) |
| [docs/API_GUIDELINES.md](docs/API_GUIDELINES.md) | Inertia conventions now; JSON API plan for later |
| [docs/DECISIONS.md](docs/DECISIONS.md) | Architectural decisions and trade-offs |

## Current Status

**Sprint 0 — Complete**

- Laravel 12 + Vue starter kit installed
- Authentication (login, register, password reset, email verification, profile)
- MySQL + Redis configured
- Docker prepared for deployment

**Sprint 1 — Complete: Identity & Multi-Tenancy**

- Organizations + row-level multi-tenancy (`organization_id`)
- Workspace-slug-scoped login; super admin via `platform` slug
- Role-based access control (`UserRole` enum + policies)
- User CRUD, deactivate/reactivate, soft delete
- Profile, avatar upload, password update
- 70 tests passing

**Next: Sprint 2 — Competition Lifecycle**
