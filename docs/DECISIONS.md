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

## ADR-0011 — Competition and Category as separate entities

- **Status:** Accepted
- **Context:** Sprint 2 domain research. One hackathon event often has multiple tracks. Future modules (Registration, Payment, Judge, Certificate) need a stable track identity.
- **Decision:** Model **Competition** as the org-owned event container (aggregate root) and **CompetitionCategory** as a child track within one competition. Categories are not global across competitions.
- **Consequences:** Registration and related modules attach to `category_id`. Slightly more complex CRUD than a flat competition model, but avoids duplicating entire events per track.

---

## ADR-0012 — Category configuration inherits from Competition with nullable overrides

- **Status:** Accepted
- **Context:** Tracks share most event settings but may differ in capacity, deadlines, or description.
- **Decision:** Competition stores defaults (`starts_at`, `ends_at`, `registration_*`, `max_participants`). Category stores nullable override columns; `null` means inherit from the parent competition.
- **Consequences:** No duplicated config across categories. A resolver for "effective" values is deferred until Registration/Payment modules require it.

---

## ADR-0013 — Four competition statuses; manual transitions

- **Status:** Accepted
- **Context:** Need a clear event lifecycle without premature sub-phases.
- **Decision:** `CompetitionStatus`: `draft`, `published`, `active`, `closed`. Transitions are explicit service actions (publish, activate, close), not automatic cron jobs in Sprint 2.
- **Consequences:** Simple state machine. Sub-statuses (`registration_open`, `judging_open`) are deferred until those modules exist.

---

## ADR-0014 — Separate category status lifecycle

- **Status:** Accepted
- **Context:** Categories need enable/disable independent of the competition state machine complexity.
- **Decision:** `CategoryStatus`: `draft`, `active`, `disabled`, `archived`. A category is only operationally open when its status is `active` **and** the parent competition is `published` or `active`.
- **Consequences:** Organizers can disable individual tracks without closing the entire event.

---

## ADR-0015 — Auto-create default "General" category on competition create

- **Status:** Accepted
- **Context:** Every published competition must have at least one category. Requiring manual category creation adds friction and risks empty events.
- **Decision:** `CreateCompetitionService` creates a default category (`name: General`, `slug: general`, `is_default: true`, `status: draft`) in the same transaction as the competition.
- **Consequences:** Guaranteed minimum structure. Default category cannot be deleted (policy/service guard); it may be renamed.

---

## ADR-0016 — OrganizationScope on Competition; Category scoped via parent

- **Status:** Accepted
- **Context:** Sprint 2 introduces the first competition-domain models. Tenant isolation must be consistent with Sprint 1 patterns.
- **Decision:** Apply `OrganizationScope` global scope to `Competition`. `CompetitionCategory` has no `organization_id`; isolation is enforced by resolving the parent competition and checking `organization_id` in policies/services.
- **Consequences:** No redundant `organization_id` on categories. Category queries for admin UIs always go through `competition_id` or eager-loaded competition.

---

## ADR-0017 — Team scoped to Competition, not Category

- **Status:** Accepted
- **Context:** Sprint 3 domain research. Teams could attach to Competition (event-wide) or Category (per track). Registration in Sprint 4 links to `category_id`.
- **Decision:** `Team` belongs to `competition_id` only. Category selection happens at registration time in Sprint 4.
- **Consequences:** One roster per event; no duplicate teams per track. Simpler Sprint 3 scope. Registration module must reference either `user_id` (individual) or `team_id` (team) plus `category_id`.

---

## ADR-0018 — Participant identity as User + ParticipantProfile

- **Status:** Accepted
- **Context:** Sprint 3 needs participant-specific fields (bio, institution) without a separate login entity.
- **Decision:** Reuse existing `User` with role `participant`. Add optional 1:1 `participant_profiles` table. Do **not** create a separate `Participant` model or auth guard.
- **Consequences:** Consistent with Sprint 1 identity (ADR-0006). Profile completeness checks reference `participant_profiles` existence. Coaches and organizers use the same `users` table.

---

## ADR-0019 — One active team per user per competition

- **Status:** Accepted
- **Context:** A user joining multiple teams in the same competition creates registration and scoring conflicts.
- **Decision:** Enforce at service layer (and optionally DB check): a user may have at most one `team_members` row with `status = active` per `competition_id`.
- **Consequences:** Accept-invitation and create-team services must query across teams in the competition. Clear error messages for users already on a roster.

---

## ADR-0020 — Team invitations limited to existing org users (Sprint 3)

- **Status:** Accepted
- **Context:** Full guest-invite flow (email → account creation → accept) adds significant scope. Sprint 3 MVP targets org-internal hackathons.
- **Decision:** `SendTeamInvitationService` resolves invitee by email **within the competition's organization** only. `invited_user_id` is always set. No outbound email in Sprint 3 — invitation appears in the invitee's in-app inbox.
- **Consequences:** Fast MVP. Guest invites and mail notifications deferred to Sprint 4. `team_invitations.email` is denormalized for display.

---

## ADR-0021 — Team approval by organizer before registration

- **Status:** Accepted
- **Context:** Organizers need to vet team rosters before official registration opens.
- **Decision:** Team status flow: `forming` → `pending_approval` → `approved` | `rejected`. Only **organizers** (same org) may approve/reject. Committee role deferred. Only `approved` teams are eligible for Sprint 4 team registration.
- **Consequences:** Extra organizer UI step. `SubmitTeamForApprovalService` validates min roster size. Individual-mode competitions skip team approval entirely.

---

## ADR-0022 — OrganizationScope on Team; related models scoped via parent

- **Status:** Accepted
- **Context:** Sprint 3 introduces team-domain models. Tenant isolation must match Sprint 2 patterns (ADR-0016).
- **Decision:** Apply `OrganizationScope` to `Team` (via `competition.organization_id`). `TeamMember`, `TeamInvitation`, and `ParticipantProfile` have no `organization_id`; isolation enforced through parent team or user in policies/services.
- **Consequences:** No redundant `organization_id` on child tables. Policies resolve org via `team.competition` using `withoutGlobalScope` when needed.

---

## Superseded / historical notes

- Early roadmap drafts assumed `spatie/laravel-permission` and invite-only registration for Sprint 1. Both were changed before implementation: roles are a PHP enum (ADR-0006) and self-serve organization signup is enabled (see [ROADMAP.md](ROADMAP.md)). Invite flow is deferred.

## Related Documents

- [PRD.md](PRD.md) — requirements these decisions serve
- [ARCHITECTURE.md](ARCHITECTURE.md) — how the decisions are realized
- [DATABASE.md](DATABASE.md) — schema shaped by ADR-0002/0004/0007
