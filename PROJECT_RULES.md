# Project Rules — Competition Management System

Engineering standards for this project. Follow these when writing new code.

## Architecture

- **Modular Monolith** — organize by domain module, not technical layer
- **Default layers:** Controllers, Form Requests, Services, Jobs, Notifications, Policies, Models
- **Actions** — only when a single-purpose command provides real value (`app/Actions/{Module}/`)
- **No Repository Pattern** unless there is a strong architectural reason
- **Prefer Laravel conventions** over custom abstractions

## Folder Structure

Module subfolders inside each layer:

```
app/Http/Controllers/{Module}/
app/Http/Requests/{Module}/
app/Services/{Module}/
app/Policies/{Module}/
app/Jobs/{Module}/
app/Events/{Module}/
app/Listeners/{Module}/
app/Notifications/{Module}/
```

Models stay flat at `app/Models/`. Enums at `app/Enums/`. Scopes at `app/Models/Scopes/`.

Frontend pages mirror backend modules: `resources/js/pages/{module}/`.

## PHP Standards

- Add `declare(strict_types=1);` to every new PHP file
- Use constructor property promotion
- Typed properties, parameters, and return types everywhere
- Use `readonly` for immutable properties
- Never call `env()` outside `config/` files — use `config('key')`
- Run `./vendor/bin/pint` before committing

## Controllers

Controllers do four things only:

1. Receive request (via Form Request)
2. Authorize (via Policy)
3. Delegate to Service
4. Return response (Inertia or redirect)

If a controller method exceeds ~15 lines, logic has leaked in.

**Exception:** Starter kit auth controllers are left as-is until we need to change their behavior.

## Services

- Own business logic orchestration
- Stateless — no request/session assumptions
- Receive explicit inputs (`User $creator`, not `auth()->user()`)
- Return domain objects, never HTTP responses
- Naming: `{Verb}{Noun}Service` (e.g. `CreateCompetitionService`)

## Validation

- Form Request classes for all user input — no inline `$request->validate()`
- Location: `app/Http/Requests/{Module}/{Action}{Model}Request.php`
- Custom reusable rules: `app/Rules/`

## Authorization

- **Authentication** = who are you (handled by starter kit)
- **Authorization** = what can you do (Policies)
- Policies at `app/Policies/{Module}/{Model}Policy.php`
- Tenant isolation via global scope (`OrganizationScope`) — never manual `where('organization_id')` in controllers

## Models

- Always define `$fillable` — never use `$guarded = []`
- Use `casts()` method for attribute casting
- Apply `OrganizationScope` on all tenant-scoped models (Sprint 1+)
- Factories required for every model used in tests

## Database

Before creating migrations, document:

- Purpose, columns, indexes, foreign keys, relationships
- No unnecessary nullable columns
- Prefer explicit constraints
- Unique constraints scoped per tenant where applicable

## Events & Jobs

- Events: past-tense domain facts (`CompetitionPublished`)
- Jobs: imperative commands (`CalculateLeaderboardJob`)
- Jobs must receive `organizationId` explicitly — never read from session
- Implement `ShouldQueue` with `$tries` and `$backoff` for external API calls

## Frontend

- Vue 3 Composition API with `<script setup lang="ts">`
- TypeScript for all new frontend code
- Reusable components in `resources/js/components/`
- Domain components in `resources/js/components/{Module}/`
- Shared types in `resources/js/types/`
- Follow kit's lowercase page folder convention: `pages/competition/`

## Security

Always consider:

- Authorization before any data access
- Mass assignment protection (`$fillable`)
- CSRF (handled by Inertia)
- File upload validation
- Rate limiting on sensitive endpoints
- No secrets in git (`.env` only locally)

## Testing

- Feature tests for HTTP/integration flows (`tests/Feature/{Module}/`)
- Unit tests for isolated business logic (`tests/Unit/Services/{Module}/`)
- Use `RefreshDatabase` for tests that write to the DB
- Never mock the database in feature tests

## Dependencies

Add packages only when they solve a real problem:

| Approved (when needed) | Avoid (until pain is felt) |
|---|---|
| `spatie/laravel-permission` | Repository pattern packages |
| `pestphp/pest` (optional) | `nwidart/laravel-modules` |
| `larastan/larastan` (optional) | `stancl/tenancy` |
| | UI component libraries beyond shadcn-vue |
| | State management (Pinia/Vuex) |

## Commit Messages

Conventional Commits format:

```
feat(scope): short description
fix(scope): short description
chore(scope): short description
```

Use imperative mood. One logical change per commit.

## Development Environment

- Local dev: `php artisan serve` + `npm run dev` (or `composer dev`)
- Docker: deployment only, not for local development
- Drivers: MySQL + Redis from day one (no SQLite for dev)
