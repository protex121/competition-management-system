# Product Requirements Document — Competition Management System

## 1. Overview

The Competition Management System (CMS) is a **multi-tenant SaaS platform** where organizations run hackathon-style competitions end to end: participants register, submit their work, judges score submissions against rubrics, and leaderboards are computed automatically.

It is built as a portfolio project to demonstrate production-quality Laravel engineering — real domain modeling, multi-tenancy, authorization, and testing — rather than a shallow CRUD demo.

## 2. Problem Statement

Organizers of hackathons and event-style competitions currently juggle spreadsheets, forms, and manual scoring. This is error-prone and does not scale across multiple concurrent events. CMS centralizes the full competition lifecycle in one tenant-isolated platform.

## 3. Goals & Non-Goals

### Goals

- Let an organization self-register and manage its own users in isolation from other tenants.
- Provide role-based access control (organizer, committee, judge, participant, coach).
- Support the full competition lifecycle: create → publish → register → submit → judge → rank.
- Enforce fairness rules (deadlines, capacity, judges cannot score their own work).
- Compute and publish leaderboards automatically.

### Non-Goals (for the current MVP)

- Billing / subscriptions (deferred; see [ROADMAP.md](ROADMAP.md)).
- Per-tenant custom branding (logos, colors).
- Native mobile clients.
- Real-time collaborative editing.

## 4. Personas & Roles

| Role | Description | Key capabilities |
|---|---|---|
| **Super Admin** | Platform operator, no organization (`organization_id = null`) | Manage users across all organizations; seeded only |
| **Organizer** | Owner of an organization (first registered user) | Manage org users, create & manage competitions |
| **Committee** | Organizer's staff | Assist with competition operations (future scope) |
| **Judge** | Scores submissions | View assigned submissions, submit scores |
| **Participant** | Competes | Register, submit work, view public results |
| **Coach** | Mentors participants/teams | Support role (future scope) |

## 5. Tenancy Model

- **Row-level multi-tenancy** on a shared database.
- Every tenant-owned record carries an `organization_id`.
- Users belong to exactly one organization; super admins belong to none.
- Email is unique **per organization**, not globally — the same email can exist in multiple orgs.
- Login is scoped by a **workspace slug**; super admins use the reserved slug `platform`.

## 6. Functional Requirements by Domain

### 6.1 Identity & User Management (implemented)

- Self-registration creates a new organization and its first organizer.
- Users can update their profile (name, email) and upload an avatar.
- Users can change their password.
- Organizers/super admins can create, edit, deactivate, reactivate, and soft-delete users.
- Deactivated users cannot authenticate; active session is invalidated on next request.
- Business rules: cannot remove yourself, cannot remove the last organizer, `super-admin` role is not assignable through the UI.

### 6.2 Competition & Category Management (Sprint 2 — planned)

A **Competition** is an org-owned event container. A **Competition Category** is a track/division within that event (e.g. Student Track, Professional Track). Future modules (Registration, Payment, Judge) attach primarily to Category.

**Competition — functional requirements:**

- Organizers create and manage competitions scoped to their organization.
- Status workflow: `draft → published → active → closed` (manual transitions in Sprint 2).
- Competition holds event-wide defaults: name, slug, description, schedule, optional global capacity and registration window.
- Slug unique per organization.
- Soft delete supported; draft competitions may be deleted.
- Public read-only page when status is `published`, `active`, or `closed`.

**Category — functional requirements:**

- Every competition has at least one category; creating a competition auto-creates a default **General** category (`slug: general`, `is_default: true`).
- Organizers add, edit, disable, and soft-delete non-default categories.
- Category status: `draft`, `active`, `disabled`, `archived`.
- Category holds nullable overrides (capacity, registration deadline, description) — null means inherit from competition.
- Slug unique per competition.
- Default category cannot be deleted (may be renamed).

**Cross-cutting rules:**

- Category `active` is meaningful only when parent competition is `published` or `active`.
- Closing a competition stops new activity on all categories.
- Only organizers (and super admin) manage competitions/categories; participants view public pages only.
- Sub-statuses (`registration_open`, `judging_open`) deferred until those modules exist.

Design detail: [COMPETITION_DESIGN.md](COMPETITION_DESIGN.md). Decisions: [DECISIONS.md](DECISIONS.md) ADR-0011–0016.

### 6.3 Registration & Teams (planned)

- Participants register **to a category** (solo or team), subject to deadlines and capacity limits.

### 6.4 Submissions (planned)

- Participants submit work (title, description, files/links) before a deadline.

### 6.5 Judging & Scoring (planned)

- Judges score submissions against rubric criteria; a judge cannot score their own submission.

### 6.6 Leaderboard & Results (planned)

- Scores aggregate into rankings via a queued job; results are publishable.

## 7. Non-Functional Requirements

| Area | Requirement |
|---|---|
| **Security** | Authorization enforced by Policies before any data access; strict mass-assignment control; validated file uploads; rate limiting on auth. |
| **Isolation** | Cross-tenant access must be impossible; proven by feature tests. |
| **Performance** | Avoid N+1 queries; eager-load relationships; queue heavy work. |
| **Maintainability** | Modular monolith; thin controllers; services own business logic. |
| **Testability** | Feature tests for HTTP flows, unit tests for isolated logic; `RefreshDatabase`. |

## 8. Success Metrics

- 100% of user-management flows covered by automated tests.
- Zero cross-tenant data leaks in the test suite.
- A reviewer can locate any artifact (controller, service, request, policy, test) from its module name alone.

## 9. Related Documents

- [ROADMAP.md](ROADMAP.md) — sprint plan and delivery status
- [ARCHITECTURE.md](ARCHITECTURE.md) — how the system is structured
- [DATABASE.md](DATABASE.md) — schema and relationships
- [API_GUIDELINES.md](API_GUIDELINES.md) — request/response conventions
- [DECISIONS.md](DECISIONS.md) — architectural decision records
- [COMPETITION_DESIGN.md](COMPETITION_DESIGN.md) — Sprint 2 competition module design
