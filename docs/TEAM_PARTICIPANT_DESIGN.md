# Team & Participant Module — Design Document

Sprint 3 design reference. **Not implemented yet** — this document is the blueprint for migrations and code.

Domain research: completed and approved — [TEAM_PARTICIPANT_RESEARCH.md](TEAM_PARTICIPANT_RESEARCH.md).  
Related ADRs: [DECISIONS.md](DECISIONS.md) ADR-0017 through ADR-0022.

---

## Domain summary

| Concept | Role |
|---|---|
| **Participant** | Existing `User` (role `participant`) extended by optional `ParticipantProfile` |
| **Team** | Named group competing in one **Competition** (not per Category) |
| **TeamMember** | Pivot linking users to teams with role and status |
| **TeamInvitation** | Pending invite for an org user to join a team |
| **Coach** | Optional `User` (role `coach`) attached to a team — P2 |

```
Organization
  └── User (participant | coach)
  └── Competition
        ├── registration_mode, min/max_team_size, requires_coach
        └── Team (0..n)
              ├── TeamMember (users)
              ├── TeamInvitation
              └── [Sprint 4] Registration → Category
```

**Boundary:** Sprint 3 manages **who** participates and **whether a team is valid**. Sprint 4 creates `Registration` records linking a user or approved team to a **category**.

---

## Status enums

### `RegistrationMode` (on `competitions`)

| Value | Meaning |
|---|---|
| `individual` | Solo participation only; no teams |
| `team` | Team participation only |
| `both` | Participant may compete solo or as a team |

Editable only while competition is `draft`. Block mode change if teams already exist.

### `TeamStatus`

| Value | Meaning |
|---|---|
| `forming` | Captain building roster; editable |
| `pending_approval` | Submitted; awaiting organizer review |
| `approved` | Eligible for Sprint 4 registration |
| `rejected` | Organizer rejected; captain may fix and resubmit |

**Transitions:**

```
forming ──submit──► pending_approval ──approve──► approved
                         │
                         └──reject──► rejected ──resubmit──► pending_approval
```

Individual-mode competitions do not use teams. `approved` is the only status eligible for registration in Sprint 4.

### `TeamMemberRole`

| Value | Meaning |
|---|---|
| `captain` | Team leader; exactly one per team |
| `member` | Regular roster member |

### `TeamMemberStatus`

| Value | Meaning |
|---|---|
| `active` | Current member |
| `removed` | Left or removed; row retained for audit |

### `InvitationStatus`

| Value | Meaning |
|---|---|
| `pending` | Awaiting response |
| `accepted` | User joined team |
| `declined` | User declined |
| `revoked` | Captain or organizer cancelled |
| `expired` | Past `expires_at` without response |

---

## Competition configuration (Sprint 3 extension)

New columns on `competitions` (migration in Issue #24 / foundation issue):

| Column | Type | Notes |
|---|---|---|
| `registration_mode` | string | Cast to `RegistrationMode`; default `individual` |
| `min_team_size` | unsigned small int, nullable | Required when mode is `team` or `both` |
| `max_team_size` | unsigned small int, nullable | Required when mode is `team` or `both`; ≥ `min_team_size` |
| `requires_coach` | boolean, default false | P2 feature; when true, team must have coach before approval |

**Validation rules:**

- `min_team_size` and `max_team_size` required when `registration_mode` is `team` or `both`.
- Cannot change `registration_mode` if any `teams` rows exist for the competition.
- Only editable when competition status is `draft`.

---

## Database design (planned)

### `participant_profiles`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint, PK | |
| `user_id` | FK → `users.id` | Unique; `cascadeOnDelete` |
| `bio` | text, nullable | Short participant bio |
| `phone` | string, nullable | Contact number |
| `institution` | string, nullable | School / company |
| `created_at` / `updated_at` | timestamp | |

**Indexes:** unique on `user_id`.

**Tenancy:** No `organization_id`. Isolation via `user.organization_id` in policies/services.

### `teams`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint, PK | |
| `competition_id` | FK → `competitions.id` | `cascadeOnDelete` |
| `name` | string | Unique per competition: `(competition_id, name)` |
| `captain_user_id` | FK → `users.id` | Denormalized for fast queries; kept in sync with `team_members` |
| `coach_user_id` | FK → `users.id`, nullable | Optional; P2 |
| `status` | string | Cast to `TeamStatus`; indexed |
| `rejection_reason` | text, nullable | Set when organizer rejects |
| `submitted_at` | timestamp, nullable | When captain submitted for approval |
| `approved_at` | timestamp, nullable | When organizer approved |
| `created_at` / `updated_at` | timestamp | |
| `deleted_at` | timestamp, nullable | Soft delete; indexed |

**Indexes:** `(competition_id, name)` unique; `status`; `captain_user_id`; `deleted_at`.

**Tenancy:** `OrganizationScope` on `Team` via `competition.organization_id` (see ADR-0022).

### `team_members`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint, PK | |
| `team_id` | FK → `teams.id` | `cascadeOnDelete` |
| `user_id` | FK → `users.id` | `cascadeOnDelete` |
| `role` | string | Cast to `TeamMemberRole` |
| `status` | string | Cast to `TeamMemberStatus`; default `active` |
| `joined_at` | timestamp | Set on create/accept |
| `created_at` / `updated_at` | timestamp | |

**Indexes:** unique `(team_id, user_id)`; index `(user_id, status)` for one-team-per-competition checks.

**Invariant:** Exactly one `active` row with `role = captain` per team.

### `team_invitations`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint, PK | |
| `team_id` | FK → `teams.id` | `cascadeOnDelete` |
| `invited_by_user_id` | FK → `users.id` | Captain or organizer |
| `invited_user_id` | FK → `users.id` | Must be same org as competition |
| `email` | string | Denormalized from invited user for display |
| `token` | string(64) | Unique; hashed or random opaque token |
| `status` | string | Cast to `InvitationStatus`; indexed |
| `expires_at` | timestamp | Default: now + 7 days |
| `responded_at` | timestamp, nullable | On accept/decline |
| `created_at` / `updated_at` | timestamp | |

**Indexes:** unique on `token`; `(team_id, invited_user_id, status)` for duplicate-pending guard.

**Sprint 3 rule:** `invited_user_id` is always set (existing org user lookup by email). Guest invites deferred to Sprint 4.

---

## Model responsibilities

### `ParticipantProfile`

- Relationships: `belongsTo(User)`
- No global scope; policies check via `user.organization_id`
- No soft deletes (profile deleted with user)

### `Team`

- Relationships: `belongsTo(Competition)`, `belongsTo(User, 'captain_user_id')`, `belongsTo(User, 'coach_user_id')`, `hasMany(TeamMember)`, `hasMany(TeamInvitation)`
- Casts: `status` → `TeamStatus`, dates → datetime
- Helpers: `isForming()`, `isPendingApproval()`, `isApproved()`, `isRejected()`, `activeMemberCount()`
- `OrganizationScope` applied in `booted()` (via competition)
- Soft deletes

### `TeamMember`

- Relationships: `belongsTo(Team)`, `belongsTo(User)`
- Casts: `role` → `TeamMemberRole`, `status` → `TeamMemberStatus`
- Scoped via parent team (no direct `organization_id`)

### `TeamInvitation`

- Relationships: `belongsTo(Team)`, `belongsTo(User, 'invited_by_user_id')`, `belongsTo(User, 'invited_user_id')`
- Casts: `status` → `InvitationStatus`, dates → datetime
- Helper: `isExpired()`, `isPending()`
- Scoped via parent team

### `Competition` (extend)

- Add casts for `registration_mode`, `min_team_size`, `max_team_size`, `requires_coach`
- Add `hasMany(Team::class)`
- Helpers: `allowsTeams()`, `allowsIndividual()`

### `User` (extend)

- Add `hasOne(ParticipantProfile::class)`, `hasMany(TeamMember::class)`

---

## Authorization

### `ParticipantProfilePolicy`

| Ability | Participant (own) | Organizer (same org) | Super admin |
|---|---|---|---|
| `view` | ✅ own | ✅ users in org | ✅ |
| `create` / `update` | ✅ own | ❌ | ✅ |
| `delete` | ❌ | ❌ | ✅ |

### `TeamPolicy`

| Ability | Captain | Member | Organizer | Super admin |
|---|---|---|---|---|
| `viewAny` | ✅ (own teams + competition context) | ✅ | ✅ same org | ✅ |
| `view` | ✅ own team | ✅ own team | ✅ same org | ✅ |
| `create` | ✅ if competition allows teams | ❌ | ❌ | ✅ |
| `update` | ✅ if `forming` or `rejected` | ❌ | ❌ | ✅ |
| `delete` | ✅ if `forming` and only captain | ❌ | ✅ draft competition | ✅ |
| `submit` | ✅ captain; team `forming`/`rejected` | ❌ | ❌ | ✅ |
| `approve` / `reject` | ❌ | ❌ | ✅ same org | ✅ |
| `invite` | ✅ captain; team `forming` | ❌ | ✅ same org | ✅ |
| `manageMembers` | ✅ captain | ❌ | ✅ same org | ✅ |
| `assignCoach` | ✅ captain (P2) | ❌ | ✅ same org | ✅ |

**Tenancy:** Resolve `competition.organization_id` via `withoutGlobalScope(OrganizationScope::class)` when needed (same pattern as `CompetitionCategoryPolicy`).

### `TeamInvitationPolicy`

| Ability | Invitee | Captain | Organizer |
|---|---|---|---|
| `view` | ✅ own invitations | ✅ team's invitations | ✅ same org |
| `accept` / `decline` | ✅ pending + not expired | ❌ | ❌ |
| `revoke` | ❌ | ✅ pending invites on own team | ✅ same org |

---

## Services (planned)

### Participant profile

| Service | Responsibility |
|---|---|
| `UpsertParticipantProfileService` | Create or update profile for actor |
| `ShowParticipantProfileService` | Load profile for actor or organizer view |

### Team CRUD

| Service | Responsibility |
|---|---|
| `CreateTeamService` | Transaction: create team + captain `team_member`; enforce one-team-per-user |
| `UpdateTeamService` | Update name; only when `forming` or `rejected` |
| `DeleteTeamService` | Soft delete; only when `forming` |
| `ListTeamsService` | Paginated list (participant: own; organizer: per competition) |
| `ShowTeamService` | Team with members and pending invitations |

### Membership

| Service | Responsibility |
|---|---|
| `TransferCaptainService` | Atomic captain role transfer |
| `RemoveTeamMemberService` | Set member status `removed`; block captain without transfer |
| `LeaveTeamService` | Member leaves; captain must transfer first |

### Invitations

| Service | Responsibility |
|---|---|
| `SendTeamInvitationService` | Lookup org user by email; create pending invitation with token |
| `RevokeTeamInvitationService` | Set status `revoked` |
| `AcceptTeamInvitationService` | Add `team_member`; enforce capacity and one-team rule |
| `DeclineTeamInvitationService` | Set status `declined` |
| `ListPendingInvitationsService` | Inbox for current user |

### Approval

| Service | Responsibility |
|---|---|
| `SubmitTeamForApprovalService` | Validate roster size; `forming`/`rejected` → `pending_approval` |
| `ApproveTeamService` | Organizer approves; set `approved_at` |
| `RejectTeamService` | Organizer rejects; optional `rejection_reason` |

### Coach (P2)

| Service | Responsibility |
|---|---|
| `AssignCoachService` | Set `coach_user_id`; validate coach role + same org |
| `RemoveCoachService` | Clear `coach_user_id` |

### Eligibility (Sprint 4 prep — read-only)

| Service | Responsibility |
|---|---|
| `CheckParticipantEligibilityService` | Can user participate in competition? Returns `EligibilityResult` |
| `CheckTeamEligibilityService` | Can team register (approved, roster size, coach if required)? |
| `CheckCompetitionParticipationService` | Facade combining mode + competition status checks |

**`EligibilityResult` DTO:**

```php
readonly class EligibilityResult
{
    public function __construct(
        public bool $eligible,
        /** @var list<string> */
        public array $reasons,
    ) {}
}
```

No HTTP routes for eligibility in Sprint 3. Sprint 4 registration services call these before creating a `Registration`.

All command services receive the **actor** explicitly (`User $actor`).

---

## Validation strategy

### Form Requests

| Request | Key rules |
|---|---|
| `UpsertParticipantProfileRequest` | bio max length, phone format, institution max length |
| `StoreTeamRequest` | name unique per competition; competition allows teams |
| `UpdateTeamRequest` | name; team in editable status |
| `SendTeamInvitationRequest` | email exists in org; team not full; no duplicate pending |
| `TransferCaptainRequest` | target user is active member |
| `SubmitTeamForApprovalRequest` | min roster size met; captain present |
| `ApproveTeamRequest` / `RejectTeamRequest` | team is `pending_approval` |
| `AssignCoachRequest` | user has `coach` role; same org |

### Service-level invariants

- One active team membership per user per competition (query `team_members` joined to `teams` on `competition_id`).
- Exactly one active captain per team.
- Team capacity: `activeMemberCount + pendingInvitations ≤ max_team_size`.
- Deactivated users cannot be invited or accept.
- Competition `closed` → all team mutations blocked.

---

## HTTP surface (planned)

### Participant routes (middleware: `auth`, `active`)

```
GET    /participant/profile
PUT    /participant/profile

GET    /competitions/{competition}/teams
GET    /competitions/{competition}/teams/create
POST   /competitions/{competition}/teams
GET    /teams/{team}
PUT    /teams/{team}
DELETE /teams/{team}

POST   /teams/{team}/submit
POST   /teams/{team}/invitations
DELETE /teams/{team}/invitations/{invitation}
POST   /teams/{team}/members/{member}/transfer-captain
DELETE /teams/{team}/members/{member}

GET    /invitations
POST   /invitations/{invitation}/accept
POST   /invitations/{invitation}/decline
```

### Organizer routes (middleware: `auth`, `active`, `organizer`)

```
GET    /competitions/{competition}/teams/review
POST   /teams/{team}/approve
POST   /teams/{team}/reject
```

Route file: `routes/teams.php` (included from `web.php`).

Module namespace: `app/Http/Controllers/Team/`, `app/Services/Team/`, `resources/js/pages/team/`.

---

## Events (deferred)

| Event | When | Sprint |
|---|---|---|
| `TeamSubmittedForApproval` | After submit | 3 (optional hook) |
| `TeamApproved` / `TeamRejected` | After organizer action | 3 (optional hook) |
| `TeamInvitationSent` | After invite | 4 (with Notification) |

Listeners and mail deferred until Notification module (Sprint 4).

---

## Testing strategy

### Unit tests

- `tests/Unit/Policies/Team/TeamPolicyTest`
- `tests/Unit/Policies/Team/TeamInvitationPolicyTest`
- `tests/Unit/Policies/Team/ParticipantProfilePolicyTest`
- Service unit tests per service (transactions, invariants, edge cases)
- Eligibility service tests with reason arrays

### Feature tests

- `tests/Feature/Team/` — CRUD, invitations, approval, tenancy isolation
- Shared `CreatesTeamFixtures` trait for factories
- Cross-org access returns 403/404
- One-team-per-user enforcement on accept
- Captain transfer and leave edge cases

### Edge cases (from research)

| Scenario | Expected |
|---|---|
| User on two teams same competition | Block on accept/create |
| Invite when team full | 422 validation error |
| Accept expired invitation | 422 / gone |
| Change registration_mode with existing teams | Block on competition update |
| Organizer approves cross-org team | 403 |

---

## Out of scope (Sprint 3)

- `registrations` table and registration UI
- Payments, submissions, judging, certificates
- Outbound email for invitations
- Guest / external email invitations (no account)
- Committee role in approval workflow
- `EffectiveCategoryConfig` resolver
- Team per category (category chosen at registration in Sprint 4)

---

## Implementation order (GitHub Issues #24–#40)

1. #24 — Migrations, models, enums, factories  
2. #25 — Competition `registration_mode` + team size settings  
3. #26 — Policies + unit tests  
4. #29 — Team CRUD services (can parallel #27 profile services after #26)  
5. #30 — Captain transfer + member removal  
6. #32 — Invitation send/revoke  
7. #33 — Accept/decline invitation  
8. #35 — Approval workflow  
9. #38 — Eligibility checker services  
10. UI issues (#28, #31, #34, #36, #39) in parallel after respective services  
11. #37 Coach (P2), #39 public hints (P2)  
12. #40 — Feature test consolidation  

Each step = one issue, one branch.

---

*Updated when Sprint 3 implementation diverges from this design.*
