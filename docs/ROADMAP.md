# Roadmap — Competition Management System

## Vision

A multi-tenant SaaS platform where organizations run hackathon-style competitions. Participants register, submit work, judges score against rubrics, and leaderboards update automatically.

Designed as a portfolio project demonstrating real-world Laravel engineering — not just CRUD.

## Domain

- **Type:** Hackathon / event-style competitions
- **Tenancy:** Multi-tenant SaaS (row-level, shared database)
- **Actors:** Platform Super Admin, Organizer, Committee, Judge, Participant, Coach

> Detailed requirements live in [PRD.md](PRD.md). Architectural decisions are logged in [DECISIONS.md](DECISIONS.md).

---

## Sprint 0 — Project Foundation ✅

**Goal:** Establish a strong, maintainable project foundation.

| Task | Status |
|---|---|
| Laravel 12 + Vue starter kit installed | ✅ |
| MySQL + Redis configured | ✅ |
| Authentication (login, register, profile) | ✅ |
| Docker prepared (deployment only) | ✅ |
| Folder structure designed | ✅ |
| Coding standards documented | ✅ |
| Development environment documented | ✅ |

---

## Sprint 1 — Identity & Multi-Tenancy ✅

**Goal:** Organizations exist in isolation. Users belong to one org. Roles determine capabilities.

**Delivered:**

- [x] `Organization` model and migration (name, slug, soft deletes)
- [x] `organization_id`, `role`, `avatar_path`, `deactivated_at`, soft deletes on `users`
- [x] Per-organization email uniqueness (`unique(organization_id, email)`)
- [x] `UserRole` PHP enum (super-admin, organizer, committee, judge, participant, coach)
- [x] Self-service registration → creates organization + first organizer
- [x] Workspace-slug-scoped login; super admin via `platform` slug
- [x] `EnsureUserIsActive` (`active`) and `EnsureOrganizer` (`organizer`) middleware
- [x] `UserPolicy` — view/create/update/delete/deactivate/reactivate/restore
- [x] User CRUD (backend services + controller + Inertia pages)
- [x] Deactivate / reactivate / soft delete
- [x] Profile update + avatar upload + password update
- [x] Super admin seeder
- [x] Feature + unit test coverage (70 tests passing)

**Decisions made** (see [DECISIONS.md](DECISIONS.md)):

- Super admin uses a nullable `organization_id` (no separate guard).
- Self-serve org signup enabled now (invite flow deferred).
- No `spatie/laravel-permission` yet — a PHP enum is sufficient.

---

## Sprint 2 — Competition & Category Management ✅

**Goal:** Organizers create and manage competitions with multiple categories (tracks).

**Domain research:** ✅ Complete — see [DECISIONS.md](DECISIONS.md) ADR-0011–0016.  
**Design doc:** ✅ [COMPETITION_DESIGN.md](COMPETITION_DESIGN.md)

### Features

- [x] `Competition` + `CompetitionCategory` models and migrations
- [x] `CompetitionStatus` + `CategoryStatus` enums
- [x] `OrganizationScope` on `Competition`
- [x] Auto-create default "General" category on competition create
- [x] Inherit-with-override config on categories
- [x] Category CRUD (nested under competition)
- [x] Competition CRUD + listing page (organizer view)
- [x] Public competition page
- [x] `CompetitionPolicy` + `CompetitionCategoryPolicy`
- [x] `CompetitionPublished` event
- [x] Feature tests (149 tests passing)

*When each item ships, this checklist and [DATABASE.md](DATABASE.md) are updated.*

---

## Sprint 3 — Team & Participant Management 🔄

**Goal:** Participants build profiles and teams; organizers approve teams. Prepares Sprint 4 registration — **no `registrations` table in this sprint**.

**Domain research:** ✅ [#22](https://github.com/protex121/competition-management-system/issues/22) — [TEAM_PARTICIPANT_RESEARCH.md](TEAM_PARTICIPANT_RESEARCH.md)  
**Design doc:** 🔄 [#23](https://github.com/protex121/competition-management-system/issues/23) — [TEAM_PARTICIPANT_DESIGN.md](TEAM_PARTICIPANT_DESIGN.md)

### Research & Design

- [x] Domain research & decisions ([#22](https://github.com/protex121/competition-management-system/issues/22))
- [ ] `TEAM_PARTICIPANT_DESIGN.md` + ADRs ([#23](https://github.com/protex121/competition-management-system/issues/23))

### Foundation

- [x] `participant_profiles`, `teams`, `team_members`, `team_invitations` ([#24](https://github.com/protex121/competition-management-system/issues/24))
- [x] `registration_mode` + team size settings on competition ([#25](https://github.com/protex121/competition-management-system/issues/25))
- [x] Policies + unit tests ([#26](https://github.com/protex121/competition-management-system/issues/26))

### Participant

- [ ] Profile services ([#27](https://github.com/protex121/competition-management-system/issues/27))
- [ ] Profile UI ([#28](https://github.com/protex121/competition-management-system/issues/28))

### Team core

- [x] Team CRUD services ([#29](https://github.com/protex121/competition-management-system/issues/29))
- [x] Captain transfer + member removal ([#30](https://github.com/protex121/competition-management-system/issues/30))
- [x] Participant team UI ([#31](https://github.com/protex121/competition-management-system/issues/31))

### Invitations

- [ ] Send / revoke invitation ([#32](https://github.com/protex121/competition-management-system/issues/32))
- [ ] Accept / decline invitation ([#33](https://github.com/protex121/competition-management-system/issues/33))
- [ ] Invitation UI ([#34](https://github.com/protex121/competition-management-system/issues/34))

### Approval

- [ ] Team approval workflow ([#35](https://github.com/protex121/competition-management-system/issues/35))
- [ ] Organizer review UI ([#36](https://github.com/protex121/competition-management-system/issues/36))

### Coach (P2)

- [ ] Optional coach assignment ([#37](https://github.com/protex121/competition-management-system/issues/37))

### Eligibility (Sprint 4 prep)

- [ ] Eligibility checker services ([#38](https://github.com/protex121/competition-management-system/issues/38))
- [ ] Public page participation hints — P2 ([#39](https://github.com/protex121/competition-management-system/issues/39))

### Quality

- [ ] Feature test consolidation ([#40](https://github.com/protex121/competition-management-system/issues/40))

---

## Sprint 4 — Registration Management

**Goal:** Participants register (solo or as approved team) to a **category**, subject to deadlines and capacity.

### Features

- [ ] `Registration` model (user or team + category + status)
- [ ] Registration flow (solo and team)
- [ ] Registration deadline enforcement (with `EffectiveCategoryConfig`)
- [ ] Capacity limits per category
- [ ] Registration confirmation notification
- [ ] Feature tests

---

## Sprint 5 — Submissions

**Goal:** Participants submit their work for judging.

### Features

- [ ] `Submission` model (title, description, files/links)
- [ ] Submit / edit / finalize submission
- [ ] Submission deadline enforcement
- [ ] File upload validation
- [ ] Submission listing (admin and participant views)
- [ ] Feature tests

---

## Sprint 6 — Judging & Scoring

**Goal:** Judges score submissions against a rubric.

### Features

- [ ] `Rubric` and `RubricCriterion` models
- [ ] `Score` model (judge/submission/criterion)
- [ ] Judge assignment to competition
- [ ] Scoring interface (Inertia page)
- [ ] Score validation (min/max per criterion)
- [ ] Prevent judges from scoring own submissions
- [ ] Feature tests

---

## Sprint 7 — Leaderboard & Results

**Goal:** Scores aggregate into rankings. Results are publishable.

### Features

- [ ] `CalculateLeaderboardJob` (queued)
- [ ] Leaderboard computation service
- [ ] Public leaderboard page
- [ ] Results export (optional)
- [ ] `CompetitionClosed` event
- [ ] Feature tests

---

## Future (Post-MVP)

| Feature | Notes |
|---|---|
| Self-serve org signup + billing | Laravel Cashier |
| Org-level branding | Logo, colors per tenant |
| Email notifications | Mailpit in dev, SMTP in prod |
| Real-time leaderboard | Laravel Broadcasting + Pusher |
| Two-factor authentication | Re-enable from starter kit |
| API for mobile clients | Laravel Sanctum (see [API_GUIDELINES.md](API_GUIDELINES.md)) |
| Audit logging | Track admin actions |
| Git + CI/CD workflow | Branch strategy, GitHub Actions |
| PHP 8.4 upgrade | Align local with CI |
| Tailwind v4 upgrade | When starter kit supports it |

---

## Engineering Milestones

Cross-cutting concerns tracked across sprints:

| Milestone | Target Sprint | Status |
|---|---|---|
| Multi-tenant isolation proven by tests | Sprint 1 | ✅ |
| First Service + Form Request + Policy pattern | Sprint 1 | ✅ |
| Competition domain researched & documented | Sprint 2 | ✅ |
| First Event + Job pipeline | Sprint 2 | ✅ |
| Team & participant domain researched | Sprint 3 | 🔄 |
| First Notification | Sprint 4 | ⏳ |
| Queue worker processing real jobs | Sprint 7 | ⏳ |
| Docker production deployment tested | Sprint 7 | ⏳ |
| Git workflow + CI on GitHub | When ready | ⏳ |
