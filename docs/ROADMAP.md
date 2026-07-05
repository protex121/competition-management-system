# Roadmap

Delivery plan, sprint by sprint. Checkboxes reflect what's actually shipped — not aspirational.

Requirements detail: [PRD.md](PRD.md). Architectural decisions: [DECISIONS.md](DECISIONS.md).

---

## Sprint 0 — Foundation ✅

Get the stack running and the conventions documented before writing domain code.

| Done | Item |
|---|---|
| ✅ | Laravel 12 + Vue starter kit |
| ✅ | MySQL + Redis |
| ✅ | Auth (login, register, profile) |
| ✅ | Docker files (deployment only) |
| ✅ | Folder structure + coding standards |
| ✅ | Dev environment documented |

---

## Sprint 1 — Identity & Multi-Tenancy ✅

Organizations exist in isolation. Users belong to one org. Roles decide what you can do.

| Done | Item |
|---|---|
| ✅ | `Organization` model (name, slug, soft deletes) |
| ✅ | User tenancy columns: `organization_id`, `role`, `avatar_path`, `deactivated_at`, soft deletes |
| ✅ | Email unique per org: `unique(organization_id, email)` |
| ✅ | `UserRole` enum — all six roles defined, two actively used |
| ✅ | Self-registration → new org + organizer |
| ✅ | Workspace-slug login; super admin via `platform` |
| ✅ | `active` + `organizer` middleware |
| ✅ | `UserPolicy` with deactivate/reactivate/restore |
| ✅ | User CRUD (services, controller, Inertia pages) |
| ✅ | Profile, avatar, password |
| ✅ | Super admin seeder |
| ✅ | 70 tests passing |

Key calls made here (full write-up in DECISIONS.md):

- Super admin = nullable `organization_id`, same guard as everyone else
- Self-serve signup now; invite flow later
- PHP enum for roles, not Spatie — one role per user is enough for now

---

## Sprint 2 — Competition Lifecycle

Organizers create and manage competitions.

- [ ] `Competition` model + migration
- [ ] `CompetitionStatus` enum (draft, published, active, closed)
- [ ] Org-scoped CRUD
- [ ] Publish / close workflow
- [ ] Listing page (organizer view)
- [ ] Public competition page
- [ ] `CompetitionPolicy`
- [ ] `CompetitionPublished` event
- [ ] Feature tests

*When this ships, PRD.md, DATABASE.md, and ARCHITECTURE.md are updated to match.*

---

## Sprint 3 — Registration & Teams

- [ ] `Registration` model
- [ ] `Team` model (optional)
- [ ] Solo + team registration flow
- [ ] Deadline + capacity enforcement
- [ ] Registration notification
- [ ] Feature tests

---

## Sprint 4 — Submissions

- [ ] `Submission` model
- [ ] Submit / edit / finalize
- [ ] Deadline enforcement
- [ ] File upload validation
- [ ] Admin + participant views
- [ ] Feature tests

---

## Sprint 5 — Judging & Scoring

- [ ] `Rubric` + `RubricCriterion`
- [ ] `Score` model
- [ ] Judge assignment
- [ ] Scoring UI (Inertia)
- [ ] Min/max validation per criterion
- [ ] Block self-scoring
- [ ] Feature tests

---

## Sprint 6 — Leaderboard & Results

- [ ] `CalculateLeaderboardJob`
- [ ] Leaderboard service
- [ ] Public leaderboard page
- [ ] Results export (maybe)
- [ ] `CompetitionClosed` event
- [ ] Feature tests

---

## Later (post-MVP)

| Feature | Notes |
|---|---|
| Billing | Laravel Cashier |
| Org branding | Logo, colors |
| Email notifications | Mailpit locally, SMTP in prod |
| Real-time leaderboard | Broadcasting + Pusher |
| 2FA | Re-enable from starter kit |
| Mobile API | Sanctum — see [API_GUIDELINES.md](API_GUIDELINES.md) |
| Audit log | Track admin actions |
| CI/CD | GitHub Actions |
| PHP 8.4 | Align local with CI |
| Tailwind v4 | When the starter kit supports it |

---

## Cross-sprint milestones

| Milestone | Sprint | Status |
|---|---|---|
| Tenant isolation proven in tests | 1 | ✅ |
| Service + Form Request + Policy pattern established | 1 | ✅ |
| First Event + Job pipeline | 2 | — |
| First Notification | 3 | — |
| Queue worker on real jobs | 6 | — |
| Docker deploy tested end-to-end | 6 | — |
| Git workflow + CI | TBD | — |

---

*This file is updated when a sprint finishes. Planned items stay listed so the direction is clear — they aren't checked off until the code and tests exist.*
