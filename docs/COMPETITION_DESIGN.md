# Competition Module — Design Document

Sprint 2 design reference. **Not implemented yet** — this document is the blueprint for migrations and code.

Domain research: completed and approved.  
Related ADRs: [DECISIONS.md](DECISIONS.md) ADR-0011 through ADR-0016.

---

## Domain summary

| Concept | Role |
|---|---|
| **Competition** | Org-owned event container (aggregate root) |
| **Competition Category** | Track/division within one competition |

```
Organization
  └── Competition (1..n)
        └── CompetitionCategory (1..n, min 1 via auto-created "General")
```

Future modules (Registration, Payment, Judge, etc.) attach primarily to **Category**, not Competition directly.

---

## Status enums

### `CompetitionStatus`

| Value | Meaning |
|---|---|
| `draft` | Setup; visible to organizers only |
| `published` | Public; registration may open (future module) |
| `active` | Event in progress |
| `closed` | Finished; read-only for participants |

Transitions are **manual** in Sprint 2 (no cron auto-transition).

Sub-statuses like `registration_open` or `judging_open` are **deferred** until those modules exist.

### `CategoryStatus`

| Value | Meaning |
|---|---|
| `draft` | Configured; not accepting registrations |
| `active` | Open track (when parent competition is `published` or `active`) |
| `disabled` | Temporarily closed to new registrations |
| `archived` | Read-only; typically when competition is `closed` |

**Rule:** A category with status `active` only accepts registrations when the parent competition is `published` or `active`.

---

## Configuration strategy: inherit with override

Competition holds **defaults**. Category holds **nullable overrides**.

| Field | On Competition | On Category | Effective value |
|---|---|---|---|
| Event dates | `starts_at`, `ends_at` | — | Competition |
| Registration window | `registration_starts_at`, `registration_ends_at` | `registration_ends_at` (override) | Category ?? Competition |
| Capacity | `max_participants` (optional global) | `max_participants` (override) | Category ?? Competition |
| Description | `description` | `description` (override) | Category ?? Competition |

Sprint 2 stores the columns. A dedicated `EffectiveCategoryConfig` resolver is introduced when Registration/Payment modules need it — not before.

---

## Database design (planned)

### `competitions`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint, PK | |
| `organization_id` | FK → `organizations.id` | Tenant owner; `cascadeOnDelete` |
| `name` | string | |
| `slug` | string | Unique per org: `(organization_id, slug)` |
| `description` | text, nullable | |
| `status` | string | Cast to `CompetitionStatus`; indexed |
| `starts_at` | timestamp, nullable | |
| `ends_at` | timestamp, nullable | Must be ≥ `starts_at` when both set |
| `registration_starts_at` | timestamp, nullable | Default reg window start |
| `registration_ends_at` | timestamp, nullable | Default reg window end |
| `max_participants` | unsigned int, nullable | Optional global cap |
| `created_at` / `updated_at` | timestamp | |
| `deleted_at` | timestamp, nullable | Soft delete; indexed |

**Indexes:** `(organization_id, slug)` unique; `status`; `deleted_at`.

### `competition_categories`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint, PK | |
| `competition_id` | FK → `competitions.id` | `cascadeOnDelete` |
| `name` | string | |
| `slug` | string | Unique per competition: `(competition_id, slug)` |
| `description` | text, nullable | Override; null = inherit competition description |
| `status` | string | Cast to `CategoryStatus`; indexed |
| `sort_order` | unsigned small int, default 0 | Display order on public page |
| `max_participants` | unsigned int, nullable | Override capacity |
| `registration_ends_at` | timestamp, nullable | Override reg deadline |
| `is_default` | boolean, default false | `true` for auto-created "General" |
| `created_at` / `updated_at` | timestamp | |
| `deleted_at` | timestamp, nullable | Soft delete |

**Indexes:** `(competition_id, slug)` unique; `status`; `is_default`.

**Default row:** On competition create, a category is auto-created:

- `name`: General  
- `slug`: general  
- `status`: draft  
- `is_default`: true  

The default category cannot be deleted (enforced in policy/service). It may be renamed.

---

## Model responsibilities

### `Competition`

- Relationships: `belongsTo(Organization)`, `hasMany(CompetitionCategory)`
- Casts: `status` → `CompetitionStatus`, dates → datetime
- Helpers: `isDraft()`, `isPublished()`, `isActive()`, `isClosed()`
- `OrganizationScope` applied in `booted()`
- Soft deletes

### `CompetitionCategory`

- Relationships: `belongsTo(Competition)`
- Casts: `status` → `CategoryStatus`, dates → datetime
- Helpers: `isActive()`, `isDefault()`
- **No** direct `organization_id` — tenant isolation via parent competition
- Soft deletes

### `Organization` (extend)

- Add `hasMany(Competition::class)`

---

## Authorization

### `CompetitionPolicy`

| Ability | Organizer (same org) | Super admin | Participant |
|---|---|---|---|
| `viewAny` | ✅ | ✅ | ❌ |
| `view` | ✅ same org | ✅ | Public if published+ |
| `create` | ✅ | ✅ | ❌ |
| `update` | ✅ draft/published (limited when active) | ✅ | ❌ |
| `delete` | ✅ draft only | ✅ | ❌ |
| `publish` | ✅ draft → published | ✅ | ❌ |
| `activate` | ✅ published → active | ✅ | ❌ |
| `close` | ✅ active/published → closed | ✅ | ❌ |

**Publish rule:** Requires at least one category with status `active` OR allows publish with categories still in `draft` (organizer must activate a track before registration opens). Recommended: **publish allowed with draft categories; registration module will require active category**.

### `CompetitionCategoryPolicy`

Authorization flows through the parent competition's organization. An organizer can manage categories only for competitions in their org.

| Ability | Rule |
|---|---|
| `create` | Competition belongs to actor's org; competition not `closed` |
| `update` | Same org; not `closed` |
| `delete` | Same org; not `is_default`; no registrations (future) |
| `disable` / `activate` | Same org; parent not `draft`/`closed` |

---

## Services (planned)

| Service | Responsibility |
|---|---|
| `CreateCompetitionService` | Transaction: create competition + default "General" category |
| `UpdateCompetitionService` | Update competition fields; reset email-verified-style rules N/A |
| `PublishCompetitionService` | Validate draft → published transition |
| `ActivateCompetitionService` | published → active |
| `CloseCompetitionService` | → closed; archive categories |
| `DeleteCompetitionService` | Soft delete; draft only |
| `ListCompetitionsService` | Org-scoped paginated list |
| `CreateCategoryService` | Add category to competition |
| `UpdateCategoryService` | Update category; validate date/capacity rules |
| `DeleteCategoryService` | Soft delete; block if `is_default` |

All services receive the **actor** explicitly (`User $actor`).

---

## Validation strategy

Form Requests per action:

| Request | Key rules |
|---|---|
| `StoreCompetitionRequest` | name, slug unique per org, dates order |
| `UpdateCompetitionRequest` | same; stricter when not draft |
| `StoreCategoryRequest` | name, slug unique per competition |
| `UpdateCategoryRequest` | capacity cannot drop below registration count (future) |
| `PublishCompetitionRequest` | authorize `publish`; competition is draft |

Slug: auto-generated from name with collision suffix (`my-event`, `my-event-2`).

---

## HTTP surface (planned)

Organizer routes (middleware: `auth`, `active`, `organizer`):

```
GET    /competitions
GET    /competitions/create
POST   /competitions
GET    /competitions/{competition}/edit
PUT    /competitions/{competition}
DELETE /competitions/{competition}
PATCH  /competitions/{competition}/publish
PATCH  /competitions/{competition}/activate
PATCH  /competitions/{competition}/close

POST   /competitions/{competition}/categories
PUT    /competitions/{competition}/categories/{category}
DELETE /competitions/{competition}/categories/{category}
PATCH  /competitions/{competition}/categories/{category}/activate
PATCH  /competitions/{competition}/categories/{category}/disable
```

Public routes (no auth, or optional auth later):

```
GET /events/{organization_slug}/{competition_slug}   # public competition page
```

Exact URL shape finalized in the routes issue.

---

## Events (planned, Sprint 2)

| Event | When |
|---|---|
| `CompetitionPublished` | After successful publish |
| `CompetitionClosed` | After close (leaderboard hook in Sprint 6) |

Listeners deferred until Notification module.

---

## Testing strategy

- **Unit:** `CompetitionPolicyTest`, `CompetitionCategoryPolicyTest`
- **Feature:** CRUD, publish/activate/close transitions, tenant isolation, default category creation, cannot delete General category
- **Edge cases:** cross-org access 403/404, publish from draft only, slug uniqueness

---

## Implementation order (for GitHub Issues)

1. Migrations + models + enums + factories  
2. Policies + unit tests  
3. Create/List/Update services + Form Requests  
4. Lifecycle services (publish, activate, close)  
5. Controller + organizer Inertia pages  
6. Category CRUD (nested under competition)  
7. Public competition page  
8. Feature test consolidation  

Each step = one issue, one branch.

---

*Updated when Sprint 2 implementation diverges from this design.*
