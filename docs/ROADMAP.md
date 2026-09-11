# Roadmap — Competition Management System

## Vision

A multi-tenant SaaS platform where organizations run hackathon-style competitions. Participants register, submit work, judges score against rubrics, and leaderboards update automatically.

Designed as a portfolio project demonstrating real-world Laravel engineering — not just CRUD.

> **Handoff / current state:** [HANDOFF.md](HANDOFF.md) (through Sprint 7 — MVP complete).

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

## Sprint 3 — Team & Participant Management ✅

**Goal:** Participants build profiles and teams; organizers approve teams. Prepares Sprint 4 registration — **no `registrations` table in this sprint**.

**Domain research:** ✅ [#22](https://github.com/protex121/competition-management-system/issues/22) — [TEAM_PARTICIPANT_RESEARCH.md](TEAM_PARTICIPANT_RESEARCH.md)  
**Design doc:** ✅ [#23](https://github.com/protex121/competition-management-system/issues/23) — [TEAM_PARTICIPANT_DESIGN.md](TEAM_PARTICIPANT_DESIGN.md)

### Research & Design

- [x] Domain research & decisions ([#22](https://github.com/protex121/competition-management-system/issues/22))
- [x] `TEAM_PARTICIPANT_DESIGN.md` + ADRs ([#23](https://github.com/protex121/competition-management-system/issues/23))

### Foundation

- [x] `participant_profiles`, `teams`, `team_members`, `team_invitations` ([#24](https://github.com/protex121/competition-management-system/issues/24))
- [x] `registration_mode` + team size settings on competition ([#25](https://github.com/protex121/competition-management-system/issues/25))
- [x] Policies + unit tests ([#26](https://github.com/protex121/competition-management-system/issues/26))

### Participant

- [x] Profile services ([#27](https://github.com/protex121/competition-management-system/issues/27))
- [x] Profile UI ([#28](https://github.com/protex121/competition-management-system/issues/28))

### Team core

- [x] Team CRUD services ([#29](https://github.com/protex121/competition-management-system/issues/29))
- [x] Captain transfer + member removal ([#30](https://github.com/protex121/competition-management-system/issues/30))
- [x] Participant team UI ([#31](https://github.com/protex121/competition-management-system/issues/31))

### Invitations

- [x] Send / revoke invitation ([#32](https://github.com/protex121/competition-management-system/issues/32))
- [x] Accept / decline invitation ([#33](https://github.com/protex121/competition-management-system/issues/33))
- [x] Invitation UI ([#34](https://github.com/protex121/competition-management-system/issues/34))

### Approval

- [x] Team approval workflow ([#35](https://github.com/protex121/competition-management-system/issues/35))
- [x] Organizer review UI ([#36](https://github.com/protex121/competition-management-system/issues/36))

### Coach (P2)

- [x] Optional coach assignment ([#37](https://github.com/protex121/competition-management-system/issues/37))

### Eligibility (Sprint 4 prep)

- [x] Eligibility checker services ([#38](https://github.com/protex121/competition-management-system/issues/38))
- [x] Public page participation hints — P2 ([#39](https://github.com/protex121/competition-management-system/issues/39))

### Quality

- [x] Feature test consolidation ([#40](https://github.com/protex121/competition-management-system/issues/40))

### UX closure (post-Sprint 3)

- [x] Participant competition browse + navigation ([#62](https://github.com/protex121/competition-management-system/issues/62))
- [x] Team membership management UI ([#63](https://github.com/protex121/competition-management-system/issues/63))
- [x] Coach assignment UI ([#64](https://github.com/protex121/competition-management-system/issues/64))
- [x] Organizer team review link ([#65](https://github.com/protex121/competition-management-system/issues/65))
- [x] Public page participation CTA ([#66](https://github.com/protex121/competition-management-system/issues/66))

See [SPRINT3_UX_CLOSURE.md](SPRINT3_UX_CLOSURE.md).

---

## Sprint 4 — Registration Management ✅

**Goal:** Participants register (solo or as approved team) to a **category**, subject to deadlines and capacity.

**Design doc:** ✅ [REGISTRATION_DESIGN.md](REGISTRATION_DESIGN.md) — ADR-0023/0024 in [DECISIONS.md](DECISIONS.md)

### Features

- [x] `Registration` model (user or team + category + status) ([#72](https://github.com/protex121/competition-management-system/issues/72))
- [x] Registration flow (solo and team) ([#74](https://github.com/protex121/competition-management-system/issues/74))
- [x] Registration deadline enforcement (with `EffectiveCategoryConfig`) ([#72](https://github.com/protex121/competition-management-system/issues/72))
- [x] Capacity limits per category — slot-based (ADR-0023) ([#74](https://github.com/protex121/competition-management-system/issues/74))
- [x] Registration confirmation notification — database channel (ADR-0024) ([#76](https://github.com/protex121/competition-management-system/issues/76))
- [x] Feature tests ([#74](https://github.com/protex121/competition-management-system/issues/74), [#76](https://github.com/protex121/competition-management-system/issues/76))
- [x] UI: My Registrations, organizer review, Register CTAs ([#78](https://github.com/protex121/competition-management-system/issues/78))

---

## Sprint 5 — Submissions ✅

**Goal:** Participants submit their work for judging.

**Design doc:** ✅ [SUBMISSION_DESIGN.md](SUBMISSION_DESIGN.md) — ADR-0025/0026 in [DECISIONS.md](DECISIONS.md)

### Features

- [x] `Submission` model (title, description, files/links) — 1:1 upsert on `Registration` ([#80](https://github.com/protex121/competition-management-system/issues/80))
- [x] Submit / edit / finalize submission ([#82](https://github.com/protex121/competition-management-system/issues/82))
- [x] Submission deadline enforcement (via `EffectiveCategoryConfig`) ([#80](https://github.com/protex121/competition-management-system/issues/80), [#82](https://github.com/protex121/competition-management-system/issues/82))
- [x] File upload validation — private `local` disk, authenticated download ([#82](https://github.com/protex121/competition-management-system/issues/82))
- [x] Submission listing (admin and participant views) ([#84](https://github.com/protex121/competition-management-system/issues/84))
- [x] Feature tests ([#82](https://github.com/protex121/competition-management-system/issues/82))

---

## Sprint 6 — Judging & Scoring ✅

**Goal:** Judges score submissions against a rubric.

**Design doc:** ✅ [JUDGING_DESIGN.md](JUDGING_DESIGN.md) — ADR-0027/0028 in [DECISIONS.md](DECISIONS.md)

### Features

- [x] `Rubric` and `RubricCriterion` models — rubric auto-created with the competition (ADR-0027) ([#86](https://github.com/protex121/competition-management-system/issues/86))
- [x] `Score` model (judge/submission/criterion) ([#86](https://github.com/protex121/competition-management-system/issues/86))
- [x] Judge assignment to competition ([#88](https://github.com/protex121/competition-management-system/issues/88))
- [x] Scoring interface (Inertia page) — judge queue + score entry ([#92](https://github.com/protex121/competition-management-system/issues/92))
- [x] Score validation (min/max per criterion) — floor fixed at 0, ceiling per criterion ([#90](https://github.com/protex121/competition-management-system/issues/90))
- [x] Prevent judges from scoring own submissions ([#86](https://github.com/protex121/competition-management-system/issues/86), [#90](https://github.com/protex121/competition-management-system/issues/90))
- [x] Feature tests ([#88](https://github.com/protex121/competition-management-system/issues/88), [#90](https://github.com/protex121/competition-management-system/issues/90))

---

## Sprint 7 — Leaderboard & Results ✅

**Goal:** Scores aggregate into rankings. Results are publishable.

**Design doc:** ✅ [LEADERBOARD_DESIGN.md](LEADERBOARD_DESIGN.md) — ADR-0029 in [DECISIONS.md](DECISIONS.md)

### Features

- [x] `CalculateLeaderboardJob` (queued) — the app's first real queued job ([#96](https://github.com/protex121/competition-management-system/issues/96))
- [x] Leaderboard computation service — per-category, average of each judge's summed score, zero-score submissions excluded ([#96](https://github.com/protex121/competition-management-system/issues/96))
- [x] Public leaderboard page ([#100](https://github.com/protex121/competition-management-system/issues/100))
- [ ] Results export — deferred (marked optional; documented as an intentional gap)
- [x] `CompetitionClosed` event ([#94](https://github.com/protex121/competition-management-system/issues/94))
- [x] Feature tests ([#94](https://github.com/protex121/competition-management-system/issues/94), [#96](https://github.com/protex121/competition-management-system/issues/96), [#98](https://github.com/protex121/competition-management-system/issues/98))

This is the last MVP sprint — the full lifecycle `create → publish → register → submit → judge → rank` is complete. See [Future (Post-MVP)](#future-post-mvp) for what's next.

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
| Team & participant domain researched | Sprint 3 | ✅ |
| First Notification | Sprint 4 | ✅ |
| Queue worker processing real jobs | Sprint 7 | ⏳ |
| Docker production deployment tested | Sprint 7 | ⏳ |
| Git workflow + CI on GitHub | When ready | ⏳ |
