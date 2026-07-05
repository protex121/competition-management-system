# Architectural Decision Records — Competition Management System

This log captures significant decisions, their context, and their consequences. Each record is immutable once accepted; if a decision changes, add a new record that supersedes the old one.

**Format:** ADR (lightweight). Status is one of `Accepted`, `Superseded`, `Deprecated`.

---

## ADR-0001 — Modular monolith over microservices

- **Status:** Accepted
- **Context:** Small team, cohesive domain, portfolio project. Need clear boundaries without operational overhead.
- **Decision:** Build a single Laravel application organized by domain module (module subfolders inside each layer).
- **Consequences:** Simple deploy and local dev; clear ownership per module; option to extract a service later if a real need appears. Requires discipline to keep modules from leaking into each other.

---

## ADR-0002 — Row-level multi-tenancy on a shared database

- **Status:** Accepted
- **Context:** Multiple organizations must be isolated, but per-tenant databases add operational complexity disproportionate to the current scale.
- **Decision:** Shared database with an `organization_id` column on tenant-owned tables. Isolation enforced in code (services/policies now; `OrganizationScope` global scope for competition-domain models later).
- **Consequences:** Cheap to operate; isolation must be proven by tests. The scope is deliberately **not** applied globally to `User` (auth and super-admin queries must cross tenants).

---

## ADR-0003 — Super admin uses a nullable `organization_id` (no separate guard)

- **Status:** Accepted
- **Context:** The platform operator must manage users across all organizations.
- **Decision:** Represent super admin as a user with `role = super-admin` and `organization_id = null`, authenticated through the same guard as everyone else.
- **Consequences:** One auth path to maintain. Login must handle the tenant-less case (see ADR-0005). Composite unique `(organization_id, email)` permits multiple `NULL`s, mitigated by seeding super admins only.

---

## ADR-0004 — Per-organization email uniqueness

- **Status:** Accepted
- **Context:** The same person (email) may participate in multiple organizations.
- **Decision:** Replace the global unique constraint on `email` with a composite unique `(organization_id, email)`.
- **Consequences:** Email + password is no longer sufficient to identify a user — login must be tenant-scoped (see ADR-0005). Validation rules for uniqueness are always scoped by `organization_id`.

---

## ADR-0005 — Workspace-slug-scoped login

- **Status:** Accepted
- **Context:** Consequence of ADR-0004 — the same email can exist in several orgs, so authentication must select a tenant.
- **Decision:** The login form takes an `organization_slug` in addition to email/password. The slug resolves the organization; credentials are matched within it. The reserved slug `platform` routes to super-admin authentication.
- **Consequences:** Slightly more friction at login (users must know their workspace), but unambiguous identity resolution. Subdomain-based tenancy was rejected as heavier than needed for now.

---

## ADR-0006 — Roles as a PHP enum, not `spatie/laravel-permission`

- **Status:** Accepted
- **Context:** Sprint 1 needs a small, fixed set of roles with one role per user.
- **Decision:** Model roles as a backed PHP enum (`UserRole`) stored in a `string` column and cast on the model. No permissions package yet.
- **Consequences:** Zero extra dependencies and migrations; type-safe role checks. If fine-grained, assignable permissions become necessary, revisit with `spatie/laravel-permission` (this ADR would be superseded).

---

## ADR-0007 — Both deactivation and soft deletes for users

- **Status:** Accepted
- **Context:** Organizers need a reversible "suspend" action distinct from removal, and removals should be recoverable.
- **Decision:** Add `deactivated_at` (reversible suspension that blocks login via `EnsureUserIsActive`) **and** `deleted_at` soft deletes (recoverable removal). These are independent states.
- **Consequences:** Clear separation between "temporarily blocked" and "removed". `UserPolicy` guards both (cannot act on self, cannot remove the last organizer). Restoration of soft-deleted users is reserved for platform admins.

---

## ADR-0008 — Authorization in Policies; business logic in Services

- **Status:** Accepted
- **Context:** Keep controllers thin and testable; avoid scattering role checks.
- **Decision:** Form Requests validate and call Policies via `authorize()`; controllers may also call `$this->authorize()`; all workflows live in stateless Services that receive explicit inputs.
- **Consequences:** Controllers stay ≤ ~15 lines. Policies are unit-testable in isolation. The base `Controller` includes `AuthorizesRequests` so any controller can authorize.

---

## ADR-0009 — Inertia now, JSON API later

- **Status:** Accepted
- **Context:** The product is a first-party web app; a general-purpose API is not yet required.
- **Decision:** Use Inertia.js for the web app. Defer a versioned JSON API (Sanctum) until a concrete consumer (e.g. mobile) exists.
- **Consequences:** No premature API surface to version and secure. Conventions for the future API are pre-defined in [API_GUIDELINES.md](API_GUIDELINES.md) so it can be added consistently.

---

## ADR-0010 — Docker for deployment only; MySQL + Redis from day one

- **Status:** Accepted
- **Context:** Reproducible production environment is desirable, but Docker-based local dev adds friction.
- **Decision:** Local development uses `php artisan serve` + `npm run dev` (`composer dev`) against local MySQL and Redis. Docker (`docker-compose.yml`, `docker/`) is reserved for deployment/CI.
- **Consequences:** Fast local iteration; production-like drivers (MySQL, Redis) used from the start rather than SQLite, avoiding driver-specific surprises.

---

## Superseded / historical notes

- Early roadmap drafts assumed `spatie/laravel-permission` and invite-only registration for Sprint 1. Both were changed before implementation: roles are a PHP enum (ADR-0006) and self-serve organization signup is enabled (see [ROADMAP.md](ROADMAP.md)). Invite flow is deferred.

## Related Documents

- [PRD.md](PRD.md) — requirements these decisions serve
- [ARCHITECTURE.md](ARCHITECTURE.md) — how the decisions are realized
- [DATABASE.md](DATABASE.md) — schema shaped by ADR-0002/0004/0007
