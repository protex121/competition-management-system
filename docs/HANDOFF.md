# Development Handoff

**Last updated:** 2026-07-06  
**Scope:** Sprint 0 through Sprint 3 (including UX closure #62–#66).  
**Does not cover:** Sprint 4 and beyond — not started.

Use this document when switching AI assistants (Claude, Cursor, etc.) or onboarding a new developer. The repository and `docs/` folder are the source of truth; this file summarizes **current state** and **how we work**.

---

## Repository

| Item | Value |
|------|-------|
| Local path | `/Users/sion/Project/Competition Management System` |
| GitHub | [protex121/competition-management-system](https://github.com/protex121/competition-management-system) |
| Integration branch | `develop` |
| Production branch | `main` |
| Latest milestone | Sprint 3 + UX closure merged to `develop` |
| Tests | **256 passing** (`php artisan test`) |

---

## Your role

Act as **Senior Software Engineer** pair-programming with a mid-level Laravel developer.

- Prioritize **maintainability** and **real engineering practices** over speed.
- Challenge weak design; explain trade-offs.
- This is a **portfolio project** — domain modeling and test coverage matter.
- Follow `PROJECT_RULES.md` and existing module conventions.

---

## Workflow (agreed process)

For each feature or GitHub issue:

1. **Requirement** — business goal, edge cases, assumptions
2. **Architecture** — align with `docs/ARCHITECTURE.md` and design docs
3. **Plan** — files to touch, tests to add
4. **One issue at a time** — implement → self-review → tests → commit
5. **PR** — one PR per issue; wait for CI (`ci` + `quality`)
6. **Merge** to `develop`; close issue; update GitHub Project board when requested

Branch naming: `feature/s{N}-short-description`, `fix/...`, `docs/...`

Commit style: conventional commits (`feat:`, `fix:`, `test:`, `docs:`).

Before commit: `./vendor/bin/pint` (PHP), `npm run lint` (frontend).

**Do not commit** unless explicitly asked (unless handoff owner delegates full autonomy).

---

## Tech stack

| Layer | Technology |
|-------|------------|
| Backend | Laravel 12, PHP 8.2+ |
| Frontend | Vue 3, TypeScript, Inertia.js |
| UI | Tailwind CSS, shadcn-vue (radix-vue) |
| Database | MySQL 8 (dev), SQLite in-memory (tests) |
| Cache / session / queue | Redis |
| Auth | Laravel starter kit (controller-based, not Fortify actions) |
| CI | GitHub Actions on `develop` and `main` |

Local dev: `composer dev` (serve + queue + logs + Vite). **Not Docker** for daily development.

---

## First-time setup

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
# Create DB: competition_management
php artisan migrate
php artisan storage:link
composer dev
```

### Dev login

| Actor | Workspace slug | Email | Password |
|-------|----------------|-------|----------|
| Super admin | `platform` | `admin@example.com` | `password` |

Organizers and participants are created via registration or organizer user management. Login URL uses workspace slug: `/login` with organization context per starter kit.

Seed super admin: `php artisan db:seed --class=SuperAdminSeeder`

---

## Documentation map (read order)

| Priority | File | Purpose |
|----------|------|---------|
| 1 | `PROJECT_RULES.md` | Coding standards — mandatory for all new code |
| 2 | `docs/ROADMAP.md` | Sprint plan and completion checklists |
| 3 | `docs/ARCHITECTURE.md` | Module layout, request flow, tenancy |
| 4 | `docs/DECISIONS.md` | ADRs — do not contradict without new ADR |
| 5 | `docs/PRD.md` | Product requirements |
| 6 | `docs/DATABASE.md` | Schema reference (migrations are source of truth) |
| Sprint 2 | `docs/COMPETITION_DESIGN.md` | Competition & category module |
| Sprint 3 | `docs/TEAM_PARTICIPANT_DESIGN.md` | Teams, invitations, approval |
| Sprint 3 | `docs/TEAM_PARTICIPANT_RESEARCH.md` | Domain research |
| UX closure | `docs/SPRINT3_UX_CLOSURE.md` | Post-Sprint 3 UI gaps closed (#62–#66) |

---

## What is complete

### Sprint 0 — Project foundation ✅

Laravel + Vue starter kit, MySQL, Redis, Docker (deploy only), auth, folder structure, coding standards.

### Sprint 1 — Identity & multi-tenancy ✅

- `Organization` model; users belong to one org (`organization_id`)
- `UserRole` enum: super-admin, organizer, committee, judge, participant, coach
- Workspace-slug login; super admin via `platform` slug
- Middleware: `active`, `organizer`
- User CRUD, deactivate/reactivate, soft delete, avatar, profile
- `OrganizationScope` on tenant-scoped models
- Policies: `UserPolicy`

**Issues:** GitHub #1–#21 (Sprint 1 range on project board).

### Sprint 2 — Competition & category management ✅

- `Competition`, `CompetitionCategory` models and lifecycle (draft → published → active → closed)
- `registration_mode`: individual / team / both; team size settings
- Organizer CRUD + category management (Inertia)
- Public page: `/events/{organization}/{competition}`
- Policies: `CompetitionPolicy`, `CompetitionCategoryPolicy`
- Services under `app/Services/Competition/`
- `CompetitionPublished` event
- `DateTimePicker` component for schedule fields (issue #60, PR #61)

**Issues:** #22–#21 sprint 2 items per `docs/ROADMAP.md`.

### Sprint 3 — Team & participant management ✅

**Boundary:** Sprint 3 answers *who participates* and *whether a team is valid*. There is **no `registrations` table** yet — that is Sprint 4.

Delivered:

| Area | Highlights |
|------|------------|
| Foundation | `participant_profiles`, `teams`, `team_members`, `team_invitations` |
| Participant | Profile services + UI (`/participant/profile`) |
| Teams | CRUD, captain, roster, submit for approval |
| Invitations | Send, revoke, accept, decline, inbox |
| Approval | Organizer review queue, approve/reject |
| Coach | Assign/remove services + UI (P2) |
| Eligibility | `CheckParticipantEligibilityService`, `CheckTeamEligibilityService` (Sprint 4 prep) |
| Policies | `TeamPolicy`, `TeamInvitationPolicy`, `ParticipantProfilePolicy` |

**Issues:** #22–#40 (see `docs/ROADMAP.md` Sprint 3 checklist).

### Sprint 3 UX closure ✅

Closed discoverability gaps between backend and UI:

| Issue | PR | What shipped |
|-------|-----|--------------|
| #62 | #67 | `/participant/competitions` browse, sidebar nav, invitation badge, dashboard cards |
| #63 | #68 | Transfer captain, remove member, leave team (routes + team show UI) |
| #64 | #69 | Coach assign/remove UI on team show |
| #65 | #70 | "Review pending teams" link on competition Edit |
| #66 | #71 | Public page participation CTA card |

Details: `docs/SPRINT3_UX_CLOSURE.md`.

---

## Route map (current)

```
routes/web.php          → dashboard, includes module route files
routes/auth.php         → login, register, password reset
routes/settings.php     → profile, password, appearance
routes/users.php        → organizer user management
routes/competitions.php → organizer competition + category CRUD, lifecycle
routes/participant.php  → participant profile, competition browse
routes/teams.php        → teams, invitations, approval, membership, coach
routes/events.php       → public competition page (guest + auth)
```

### Key named routes

| Route name | Path | Who |
|------------|------|-----|
| `participant.competitions.index` | `/participant/competitions` | Participant |
| `participant.profile.edit` | `/participant/profile` | Participant |
| `competitions.teams.index` | `/competitions/{id}/teams` | Participant |
| `teams.show` | `/teams/{team}` | Team member / organizer |
| `invitations.index` | `/invitations` | Participant |
| `competitions.teams.review` | `/competitions/{id}/teams/review` | Organizer |
| `competitions.edit` | `/competitions/{id}/edit` | Organizer |
| `events.competitions.show` | `/events/{org}/{competition}` | Public |

---

## Module layout (backend)

```
app/Http/Controllers/
├── Identity/          → UserController
├── Settings/          → Profile, Password (starter kit)
├── Competition/       → Competition, Category, Public, ParticipantCompetition
└── Team/              → Team, TeamMember, TeamCoach, TeamInvitation, TeamApproval, ParticipantProfile

app/Services/
├── Identity/
├── Competition/
└── Team/

app/Policies/
├── Identity/
├── Competition/
└── Team/

resources/js/pages/
├── identity/users/
├── competition/competitions/   → organizer
├── competition/public/         → public show
├── participant/                → browse, profile
└── team/                       → teams, invitations
```

---

## Multi-tenancy rules

1. Tenant-scoped models use `OrganizationScope` — never manual `where('organization_id')` in controllers.
2. Background jobs must receive `organization_id` explicitly.
3. Email uniqueness is per organization: `unique(organization_id, email)`.
4. Cross-org access should return **404** (not 403) for scoped resources.

---

## GitHub project

| Item | Value |
|------|-------|
| Owner | `protex121` |
| Project | Competition Management System (project #1) |
| Project ID | `PVT_kwHOAlhBfs4BcglO` |
| Status field | `PVTSSF_lAHOAlhBfs4BcglOzhXI1Ts` |
| Done option ID | `98236657` |

Issues #62–#66 are closed and marked **Done** on the board.

---

## Manual test flows (smoke)

### Organizer

1. Login → Competitions → create/edit competition
2. Publish → activate (if needed)
3. Competition Edit → **Review pending teams** (team-mode competitions)
4. Approve/reject submitted teams

### Participant

1. Login → Sidebar **Competitions** → join or create team
2. Team show → invite, assign coach, transfer captain, submit for approval
3. Sidebar **Invitations** (badge when pending)

### Public

1. Visit `/events/{org-slug}/{competition-slug}`
2. **Participate** card: guest sees login/register; participant sees join/view team

---

## Known intentional gaps (not bugs)

These were deferred by design — **do not implement without a new sprint/issue**:

- `registrations` table and category registration flow (Sprint 4)
- Email notifications for invitations
- Individual registration UI (only team flows are complete)
- Committee / judge roles (enum exists, features not built)
- JSON API (Inertia only for now; see `docs/API_GUIDELINES.md`)

---

## Prompt template (copy for new AI session)

```markdown
Continue the Competition Management System on branch `develop`.

Read first:
1. docs/HANDOFF.md (this file)
2. PROJECT_RULES.md
3. docs/ROADMAP.md

Current state: Sprint 0–3 complete including UX closure (#62–#66).
256 tests passing. Do NOT start Sprint 4 unless I explicitly ask.

Workflow: one GitHub issue at a time, PR per issue, run tests before merge.

My task for this session:
[describe issue or goal]
```

---

## Recent merge history (UX closure)

```
PR #67  feat(ux): participant competition browse (#62)
PR #68  feat(ux): team membership actions (#63)
PR #69  feat(ux): coach assignment UI (#64)
PR #70  feat(ux): organizer team review link (#65)
PR #71  feat(ux): public page participation CTA (#66)
```

For full history: `git log develop --oneline` or GitHub PR list.

---

## What comes next (not started — do not implement from this doc)

Sprint 4 — **Registration Management** is planned in `docs/ROADMAP.md` but **not implemented**. Start only when the owner prompts with explicit Sprint 4 requirements and issues.
