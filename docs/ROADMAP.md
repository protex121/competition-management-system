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

## Sprint 2 — Competition Lifecycle

**Goal:** Organizers can create and manage competitions.

### Features

- [ ] `Competition` model (name, slug, description, status, dates)
- [ ] `CompetitionStatus` enum (draft, published, active, closed)
- [ ] CRUD for competitions (org-scoped)
- [ ] Publish / close competition workflow
- [ ] Competition listing page (organizer view)
- [ ] Public competition page (for participants)
- [ ] `CompetitionPolicy`
- [ ] `CompetitionPublished` event
- [ ] Feature tests

---

## Sprint 3 — Registration & Teams

**Goal:** Participants can register for competitions, optionally as teams.

### Features

- [ ] `Registration` model (user/competition/status)
- [ ] `Team` model (optional grouping)
- [ ] Registration flow (solo and team)
- [ ] Registration deadline enforcement
- [ ] Capacity limits per competition
- [ ] Registration confirmation notification
- [ ] Feature tests

---

## Sprint 4 — Submissions

**Goal:** Participants submit their work for judging.

### Features

- [ ] `Submission` model (title, description, files/links)
- [ ] Submit / edit / finalize submission
- [ ] Submission deadline enforcement
- [ ] File upload validation
- [ ] Submission listing (admin and participant views)
- [ ] Feature tests

---

## Sprint 5 — Judging & Scoring

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

## Sprint 6 — Leaderboard & Results

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
| First Event + Job pipeline | Sprint 2 | ⏳ |
| First Notification | Sprint 3 | ⏳ |
| Queue worker processing real jobs | Sprint 6 | ⏳ |
| Docker production deployment tested | Sprint 6 | ⏳ |
| Git workflow + CI on GitHub | When ready | ⏳ |
