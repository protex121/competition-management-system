# Product Requirements

What this app is supposed to do, and what it explicitly won't do (yet).

## The idea

Organizations run hackathon-style competitions on this platform. Participants register, submit work, judges score it, and leaderboards get computed from those scores.

The hard part isn't CRUD — it's getting tenancy, roles, deadlines, and fairness rules right. That's what the early sprints focus on.

## Problem

Running a competition with spreadsheets and Google Forms works once. It falls apart when you have multiple events, multiple orgs, or need audit trails. This app centralizes that lifecycle in one place, with each organization's data kept separate.

## Goals

- Organizations can sign up and manage their own users without seeing other tenants' data.
- Roles control what you can do: organizer, judge, participant, etc.
- Full competition flow: create → publish → register → submit → judge → rank.
- Fairness enforced in code: deadlines, capacity limits, judges can't score their own submissions.

## Out of scope for now

- Billing / subscriptions
- Custom branding per org
- Mobile apps
- Real-time collaboration

See [ROADMAP.md](ROADMAP.md) for when these might land.

## Roles

| Role | Who | Can do |
|---|---|---|
| Super Admin | Platform operator (`organization_id = null`) | Manage users across all orgs. Created via seeder only. |
| Organizer | First user who registers an org | Manage users, run competitions |
| Committee | Org staff | Help run events *(not built yet)* |
| Judge | Assigned scorer | Score submissions *(Sprint 5)* |
| Participant | Competitor | Register and submit *(Sprint 3–4)* |
| Coach | Mentor | Support role *(future)* |

## Tenancy

Shared database, row-level isolation via `organization_id`.

A few things that matter early:

- Users belong to one org. Super admins belong to none.
- Email is unique **per org**, not globally — same person can exist in multiple orgs.
- Login requires a **workspace slug** so we know which org to authenticate against. Super admins use the slug `platform`.

More detail in [DECISIONS.md](DECISIONS.md) (ADR-0002 through ADR-0005).

## Features by area

### Identity & users — done (Sprint 1)

- Register → creates org + first organizer
- Login scoped by workspace slug
- Profile update, avatar upload, password change
- Organizer/super-admin user management: create, edit, deactivate, reactivate, soft delete
- Deactivated users can't log in; existing sessions get killed on next request
- Guard rails: can't delete yourself, can't delete the last organizer, can't assign `super-admin` via UI

### Competition lifecycle — Sprint 2

- CRUD for competitions with status workflow: draft → published → active → closed
- Public competition page for participants

### Registration & teams — Sprint 3

- Solo or team registration, deadlines, capacity limits

### Submissions — Sprint 4

- Submit work (title, description, files/links) before deadline

### Judging — Sprint 5

- Rubric-based scoring; judges can't score their own work

### Leaderboard — Sprint 6

- Queued job aggregates scores; publishable results

## Non-functional expectations

These aren't nice-to-haves — they're the baseline expectations for the codebase:

- **Security:** Policies before data access. Strict `$fillable`. Validated uploads.
- **Isolation:** Cross-tenant access must fail. Covered by feature tests.
- **Performance:** Eager-load relationships. Queue heavy work.
- **Maintainability:** Thin controllers, logic in services, modules by domain.
- **Tests:** Feature tests for HTTP flows. No mocking the DB in feature tests.

## Sprint 1 acceptance criteria

- User management flows have automated test coverage.
- An organizer cannot see or touch another org's users (tested).
- Given a module name (e.g. `Identity`), you can find the controller, service, request, policy, and tests without guessing.

---

*This doc is updated as sprints ship. Planned sections stay here as a north star — they're marked done when the code lands.*
