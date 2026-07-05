# Decisions

A log of architectural choices — what was chosen, what was rejected, and why. When a decision changes, a new entry is added rather than editing the old one.

Format: lightweight ADR. Status = `Accepted` | `Superseded` | `Deprecated`.

---

## Modular monolith, not microservices

**Accepted**

One Laravel app, modules as subfolders (`Identity/`, `Competition/`, etc.).

The domain is a single product and the team is small. Microservices would mean more infra for little benefit at this scale. If a module ever needs to scale independently, the folder boundaries make extraction possible — but that's not being optimized for prematurely.

---

## Row-level tenancy on a shared database

**Accepted**

`organization_id` on tenant-owned rows. One database.

Per-tenant databases would give cleaner isolation but add ops work this project doesn't need yet. Isolation is enforced in code and proven in tests — that's the trade-off.

**Important:** `OrganizationScope` will go on competition-domain models (Sprint 2+), but **not** on `User`. Login and super-admin queries need to cross org boundaries.

---

## Super admin = nullable `organization_id`, same guard

**Accepted**

No separate admin guard or admin table. Super admin is a user with `role = super-admin` and `organization_id = null`.

One auth path to maintain. Downside: login needs a special case for tenant-less users (see next entry). Also, MySQL's unique index treats `NULL`s as distinct, so super admins are seeded only — no UI to create them.

---

## Email unique per organization, not globally

**Accepted**

The global `unique(email)` was dropped. `unique(organization_id, email)` was added instead.

Same person can join multiple orgs with the same email — realistic for hackathons. Cost: login can't be email + password alone anymore; the workspace slug is required.

---

## Workspace slug on the login form

**Accepted**

Login sends `organization_slug` + email + password. The slug resolves the org; auth happens within it.

Subdomain tenancy (`acme.app.com/login`) was considered and rejected as overkill for Sprint 1. The slug field adds a bit of login friction but keeps identity unambiguous. Reserved slug `platform` for super admin.

---

## PHP enum for roles, not Spatie Permission

**Accepted**

`UserRole` backed enum, one role per user, stored as a string column.

Sprint 1 needs six role labels but only assigns two (`super-admin` via seeder, `organizer` via registration). Spatie would add tables, config, and overhead for no current benefit. If granular assignable permissions become necessary later, this decision gets revisited and likely superseded.

---

## Deactivation AND soft delete — two different things

**Accepted**

- `deactivated_at` — suspend login, reversible, user still visible in lists
- `deleted_at` — soft delete, user disappears from lists, recoverable by platform admin

This distinction came up during user management: organizers need to block someone without removing all trace of them. Separate fields, separate policy methods, separate UI buttons.

Guard rails on both: can't act on yourself, can't remove the last organizer.

---

## Policies for auth, Services for logic

**Accepted**

Form Requests call Policies. Controllers can too. All workflow logic lives in Services with explicit inputs (`User $actor`, not `auth()->user()` inside the service).

During Sprint 1, `UserController` called `$this->authorize()` but Laravel 12's empty base `Controller` didn't include the trait. Fixed by adding `AuthorizesRequests`. The pattern is worth keeping: controllers stay thin, policies are unit-testable.

---

## Inertia now, JSON API when there's a consumer

**Accepted**

The web app uses Inertia. No REST API until a concrete consumer exists (mobile, integrations).

The API surface isn't versioned and secured prematurely. [API_GUIDELINES.md](API_GUIDELINES.md) documents the planned conventions so they're ready when needed.

---

## Docker for deploy, not local dev

**Accepted**

Local: `composer dev` against MySQL + Redis. Docker files exist for production/CI only.

Docker-based local dev adds friction (rebuilds, volume mounts, debugging through containers). Local iteration is prioritized; the Docker setup gets validated when deployment matters (Sprint 6 target).

MySQL + Redis are used from day one, not SQLite for dev — driver-specific issues get caught early.

---

## Revised during planning (historical)

Early planning assumed Spatie Permission and invite-only registration for Sprint 1. Both changed before implementation:

- Roles → PHP enum (above)
- Invite-only → self-serve signup enabled; invite flow deferred to post-MVP

The old ROADMAP items document what was deliberately not built and why.

---

*New decisions are appended here. Superseded ones are kept for context.*
