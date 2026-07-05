# Project Rules

Coding standards for this repo. These reflect patterns already in use — not aspirational guidelines. If something here conflicts with the code, the code wins and this doc gets updated.

Full architecture context: [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md).

---

## Structure

Modular monolith. Group by domain module, not by layer:

```
app/Http/Controllers/{Module}/
app/Http/Requests/{Module}/
app/Services/{Module}/
app/Policies/{Module}/
app/Jobs/{Module}/
app/Events/{Module}/
app/Notifications/{Module}/
```

Models live flat at `app/Models/`. Enums at `app/Enums/`. Scopes at `app/Models/Scopes/`.

Frontend mirrors backend: `resources/js/pages/{module}/`.

No repository pattern unless there's a concrete reason. No action classes unless a single-purpose command genuinely helps — services are the default.

---

## PHP

- `declare(strict_types=1);` on every new file
- Constructor property promotion, typed everything
- `readonly` where it makes sense
- `env()` only in config files — use `config()` elsewhere
- Run `./vendor/bin/pint` before committing

---

## Controllers

Four jobs:

1. Receive (Form Request)
2. Authorize (Policy)
3. Delegate (Service)
4. Respond (Inertia or redirect)

~15 lines per method. If it's longer, something leaked.

**Exception:** starter kit auth controllers stay as-is until a behavior change is required.

Base `Controller` includes `AuthorizesRequests` so `$this->authorize()` works everywhere.

---

## Services

- Own the business logic
- Stateless — pass the actor explicitly (`User $actor`), don't call `auth()` inside
- Return domain objects, not HTTP responses
- Name: `{Verb}{Noun}Service` — `CreateUserService`, `DeactivateUserService`

Sensitive fields like `deactivated_at` are set directly on the model in the service, not via mass assignment.

---

## Validation

Form Requests for all user input. No inline `$request->validate()`.

Path: `app/Http/Requests/{Module}/{Action}{Model}Request.php`

Reusable rules go in `app/Rules/`.

---

## Authorization

- Auth (who you are) → starter kit
- Authz (what you can do) → Policies at `app/Policies/{Module}/{Model}Policy.php`

Tenant isolation: services and policies check `organization_id`. Global `OrganizationScope` is planned for competition models (Sprint 2+) — not on `User`.

Role checks don't belong in controllers.

---

## Models

- Always `$fillable` — never `$guarded = []`
- `casts()` method for attribute casting
- Factory for every model used in tests
- `$appends` sparingly (e.g. `avatar_url` accessor)

---

## Database

Before writing a migration, document:

- Purpose, indexes, foreign keys, relationships
- Avoid nullable columns "just in case"
- Scope unique constraints per tenant where relevant

New tables are documented in [docs/DATABASE.md](docs/DATABASE.md) when their sprint ships.

---

## Events & Jobs

- Events: past tense — `CompetitionPublished`
- Jobs: imperative — `CalculateLeaderboardJob`
- Jobs get `organizationId` in the constructor, never from session
- `ShouldQueue` + `$tries` + `$backoff` for anything hitting external APIs

---

## Frontend

- Vue 3, `<script setup lang="ts">`, TypeScript
- Shared components in `resources/js/components/`
- Module-specific components in `resources/js/components/{Module}/`
- Types in `resources/js/types/`
- Page folders lowercase: `pages/identity/users/`

Use server-provided `can` props for conditional UI. Frontend-only checks are not sufficient for security.

---

## Security checklist

- Policy before data access
- `$fillable` strict — sensitive fields set in services
- CSRF handled by Inertia
- Validate file uploads (type, size, dimensions)
- Rate limit auth endpoints
- No secrets in git

---

## Testing

- Feature tests: `tests/Feature/{Module}/` — full HTTP, real DB, `RefreshDatabase`
- Unit tests: policies, pure service logic
- The database is not mocked in feature tests
- Tenant isolation is tested explicitly

---

## Dependencies

Add a package only when the pain is real:

| OK when needed | Skip for now |
|---|---|
| `spatie/laravel-permission` | Repository packages |
| `pestphp/pest` | `nwidart/laravel-modules` |
| `larastan/larastan` | `stancl/tenancy` |
| | Pinia / Vuex |
| | Extra UI libraries beyond shadcn-vue |

---

## Commits

Conventional Commits:

```
feat(identity): add user deactivation
fix(auth): scope login to workspace slug
chore(docs): update roadmap for sprint 1
```

Imperative mood. One logical change per commit.

---

## Dev environment

- Local: `composer dev` (serve + Vite + queue + logs)
- DB: MySQL. Cache/session/queue: Redis. Not SQLite for dev.
- Docker: deployment only — see `docker-compose.yml`

---

*Updated when conventions change. See [docs/DECISIONS.md](docs/DECISIONS.md) for the reasoning behind these rules.*
