# Database — Competition Management System

This document describes the schema. It is kept in sync with the migrations in `database/migrations/`. Tables marked *(planned)* are on the roadmap and not yet migrated.

## Conventions

- Primary keys: auto-increment `id` (`bigint unsigned`).
- Timestamps: `created_at`, `updated_at` on all domain tables.
- Soft deletes: `deleted_at` where records must be recoverable.
- Tenant ownership: `organization_id` foreign key on tenant-owned tables.
- Unique constraints are **scoped per tenant** where applicable.
- Column names: `snake_case`. Enums stored as `string` columns + PHP enum casts (not native MySQL `ENUM`).

## Entity Relationship Overview

```mermaid
erDiagram
    ORGANIZATIONS ||--o{ USERS : "has many"
    ORGANIZATIONS ||--o{ COMPETITIONS : "has many"
    COMPETITIONS ||--o{ COMPETITION_CATEGORIES : "has many"
    COMPETITIONS ||--o{ TEAMS : "Sprint 3"
    USERS ||--|| PARTICIPANT_PROFILES : "1:1, Sprint 3"
    USERS ||--o{ TEAM_MEMBERS : "Sprint 3"
    TEAMS ||--o{ TEAM_MEMBERS : "has many"
    TEAMS ||--o{ TEAM_INVITATIONS : "has many"
    USERS ||--o{ TEAM_INVITATIONS : "invited"
    COMPETITION_CATEGORIES ||--o{ REGISTRATIONS : "Sprint 4"
    USERS ||--o{ REGISTRATIONS : "Sprint 4"
    TEAMS ||--o{ REGISTRATIONS : "Sprint 4, optional"
    COMPETITIONS ||--o{ SUBMISSIONS : "planned"
    USERS ||--o{ SUBMISSIONS : "planned"
    SUBMISSIONS ||--o{ SCORES : "planned"
    USERS ||--o{ SCORES : "judges, planned"
```

---

## Tables (implemented)

### `organizations`

The tenant root. Each organization owns its users and (future) competitions.

| Column | Type | Notes |
|---|---|---|
| `id` | bigint unsigned, PK | |
| `name` | string | Display name |
| `slug` | string, **unique** | Used at login as the workspace identifier |
| `created_at` / `updated_at` | timestamp | |
| `deleted_at` | timestamp, nullable | Soft delete; indexed |

**Indexes:** unique on `slug`; index on `deleted_at`.

**Relationships:** `hasMany(User::class)`.

---

### `users`

Extends the starter-kit users table with identity and tenancy columns.

| Column | Type | Notes |
|---|---|---|
| `id` | bigint unsigned, PK | |
| `organization_id` | bigint unsigned, FK → `organizations.id`, nullable | `null` for super admins; `nullOnDelete` |
| `name` | string | |
| `email` | string | Unique **per organization** (see below) |
| `role` | string, default `participant` | Cast to `UserRole` enum; indexed |
| `avatar_path` | string, nullable | Stored on the `public` disk |
| `email_verified_at` | timestamp, nullable | |
| `password` | string | `hashed` cast |
| `remember_token` | string, nullable | |
| `deactivated_at` | timestamp, nullable | Set → user cannot authenticate |
| `created_at` / `updated_at` | timestamp | |
| `deleted_at` | timestamp, nullable | Soft delete; indexed |

**Indexes & constraints:**

- **Composite unique** `(organization_id, email)` — replaces the default global unique on `email`.
- Index on `role`.
- Index on `deleted_at`.
- Foreign key `organization_id` → `organizations.id`, `nullOnDelete`.

**Note on per-tenant uniqueness:** MySQL allows multiple `NULL`s in a unique index, so several super admins with `organization_id = null` would not collide on the composite key. This is mitigated by only creating super admins via the seeder and by validating global uniqueness among `organization_id IS NULL` rows when needed.

**Relationships:** `belongsTo(Organization::class)`.

---

### Starter-kit support tables

| Table | Purpose |
|---|---|
| `password_reset_tokens` | Password reset flow |
| `sessions` | Database-less by default (Redis session), retained for compatibility |
| `cache`, `cache_locks` | Cache store fallback |
| `jobs`, `job_batches`, `failed_jobs` | Queue infrastructure |

---

## Tables (Sprint 2 — implemented)

Full design: [COMPETITION_DESIGN.md](COMPETITION_DESIGN.md).

### `competitions`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint unsigned, PK | |
| `organization_id` | FK → `organizations.id` | Tenant owner; cascade on delete |
| `name` | string | |
| `slug` | string | Unique per org: `(organization_id, slug)` |
| `description` | text, nullable | |
| `status` | string | `CompetitionStatus`: draft/published/active/closed |
| `starts_at` / `ends_at` | timestamp, nullable | Event schedule |
| `registration_starts_at` / `registration_ends_at` | timestamp, nullable | Default registration window |
| `max_participants` | unsigned int, nullable | Optional global capacity |
| `registration_mode` | string, nullable | *(Sprint 3)* `RegistrationMode`: individual/team/both |
| `min_team_size` | unsigned small int, nullable | *(Sprint 3)* Min roster when team mode |
| `max_team_size` | unsigned small int, nullable | *(Sprint 3)* Max roster when team mode |
| `requires_coach` | boolean, default false | *(Sprint 3, P2)* Coach required before approval |
| timestamps + `deleted_at` | | Soft delete |

### `competition_categories`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint unsigned, PK | |
| `competition_id` | FK → `competitions.id` | Cascade on delete |
| `name` | string | |
| `slug` | string | Unique per competition: `(competition_id, slug)` |
| `description` | text, nullable | Override; null = inherit |
| `status` | string | `CategoryStatus`: draft/active/disabled/archived |
| `sort_order` | unsigned small int | Display order; default 0 |
| `max_participants` | unsigned int, nullable | Override capacity |
| `registration_ends_at` | timestamp, nullable | Override reg deadline |
| `is_default` | boolean | `true` for auto-created General |
| timestamps + `deleted_at` | | Soft delete |

## Tables (Sprint 3 — implemented)

Full design: [TEAM_PARTICIPANT_DESIGN.md](TEAM_PARTICIPANT_DESIGN.md).

### `participant_profiles`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint unsigned, PK | |
| `user_id` | FK → `users.id` | Unique; cascade on delete |
| `bio` | text, nullable | |
| `phone` | string, nullable | |
| `institution` | string, nullable | |
| timestamps | | |

### `teams`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint unsigned, PK | |
| `competition_id` | FK → `competitions.id` | Cascade on delete |
| `name` | string | Unique per competition: `(competition_id, name)` |
| `captain_user_id` | FK → `users.id` | Denormalized; synced with `team_members` |
| `coach_user_id` | FK → `users.id`, nullable | Optional coach (P2) |
| `status` | string | `TeamStatus`: forming/pending_approval/approved/rejected |
| `rejection_reason` | text, nullable | |
| `submitted_at` / `approved_at` | timestamp, nullable | Approval workflow |
| timestamps + `deleted_at` | | Soft delete |

### `team_members`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint unsigned, PK | |
| `team_id` | FK → `teams.id` | Cascade on delete |
| `user_id` | FK → `users.id` | Cascade on delete |
| `role` | string | `TeamMemberRole`: captain/member |
| `status` | string | `TeamMemberStatus`: active/removed |
| `joined_at` | timestamp | |
| timestamps | | |

**Unique:** `(team_id, user_id)`.

### `team_invitations`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint unsigned, PK | |
| `team_id` | FK → `teams.id` | Cascade on delete |
| `invited_by_user_id` | FK → `users.id` | Captain or organizer |
| `invited_user_id` | FK → `users.id` | Existing org user only (Sprint 3) |
| `email` | string | Denormalized for display |
| `token` | string(64) | Unique opaque token |
| `status` | string | `InvitationStatus` |
| `expires_at` | timestamp | Default 7 days |
| `responded_at` | timestamp, nullable | |
| timestamps | | |

---

## Tables (Sprint 4+ — planned)

### `registrations` *(Sprint 4)*

Links a user (solo) or **approved team** to a **competition category**, with status and deadline/capacity enforcement.

### `submissions` *(Sprint 5)*

Participant work: title, description, files/links, finalized state, deadline enforcement.

### `rubrics` / `rubric_criteria` *(Sprint 6)*

Scoring structure for a competition.

### `scores` *(Sprint 6)*

A judge's score for a submission against a criterion; a judge cannot score their own submission.

---

## Migrations

| Migration | Creates / Alters |
|---|---|
| `0001_01_01_000000_create_users_table` | `users`, `password_reset_tokens`, `sessions` |
| `0001_01_01_000001_create_cache_table` | `cache`, `cache_locks` |
| `0001_01_01_000002_create_jobs_table` | `jobs`, `job_batches`, `failed_jobs` |
| `2026_07_03_000001_create_organizations_table` | `organizations` |
| `2026_07_03_000002_add_identity_columns_to_users_table` | Adds tenancy/identity columns to `users` |
| `2026_07_05_000001_create_competitions_table` | `competitions` |
| `2026_07_05_000002_create_competition_categories_table` | `competition_categories` |
| `2026_07_06_000001_add_team_settings_to_competitions_table` | Team settings on `competitions` |
| `2026_07_06_000002_create_participant_profiles_table` | `participant_profiles` |
| `2026_07_06_000003_create_teams_table` | `teams` |
| `2026_07_06_000004_create_team_members_table` | `team_members` |
| `2026_07_06_000005_create_team_invitations_table` | `team_invitations` |

## Seed Data

`SuperAdminSeeder` creates a single platform administrator:

- Email: `admin@example.com`
- Password: `password`
- Role: `super-admin`
- `organization_id`: `null`

Run with `php artisan migrate --seed`. These are local development credentials only — never used in production.

## Related Documents

- [ARCHITECTURE.md](ARCHITECTURE.md) — how tenancy and scoping work
- [DECISIONS.md](DECISIONS.md) — architectural decision records
- [COMPETITION_DESIGN.md](COMPETITION_DESIGN.md) — Sprint 2 competition module design
- [TEAM_PARTICIPANT_DESIGN.md](TEAM_PARTICIPANT_DESIGN.md) — Sprint 3 team & participant module design
