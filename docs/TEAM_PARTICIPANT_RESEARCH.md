# Team & Participant — Domain Research (Sprint 3)

**Status:** Approved (Opsi A, 2026-07-06)  
**GitHub Issue:** [#22](https://github.com/protex121/competition-management-system/issues/22)  
**Next step:** Issue [#23](https://github.com/protex121/competition-management-system/issues/23) — design document + ADRs

Domain research for Sprint 3 — Team & Participant Management. This document locks decisions before schema and implementation work. Sprint 2 delivered the competition container; Sprint 3 introduces people and teams; Sprint 4 will add official registration to categories.

---

## Sprint goal

| Sprint | Focus |
|---|---|
| Sprint 2 ✅ | Competition, Category, lifecycle, public page |
| **Sprint 3** | Participant profile, teams, invitations, approval, eligibility |
| Sprint 4 | Registration to category (uses Sprint 3 entities) |

Sprint 3 answers **who participates** and **whether a team is valid**. Sprint 4 answers **which category they register for**.

---

## Approved decisions (Opsi A)

| # | Decision | Choice |
|---|---|---|
| 1 | Team scope | **Per Competition** (not per Category) |
| 2 | Participant identity | **Existing `User`** + `participant_profiles` (no separate Participant model) |
| 3 | Invitations (Sprint 3) | **Existing org users only** (lookup by email within tenant) |
| 4 | One team per user | **One active team per user per competition** |
| 5 | Team approval | Captain submits → **organizer** approves/rejects |
| 6 | Coach | **P2 optional** — may defer if schedule is tight |
| 7 | Competition config | Add `registration_mode`, `min_team_size`, `max_team_size`, `requires_coach` |
| 8 | Out of scope Sprint 3 | `registrations`, payments, submissions, full email notifications, guest invites, `EffectiveCategoryConfig` |

---

## Domain concepts

### Participant

- A `User` with role `participant` (or acting in participant context) within their organization.
- Extended by optional 1:1 `participant_profiles` (bio, phone, institution — minimal field set).
- Future: `Registration` links user or team to a **category** in Sprint 4.

**Rules:** Same org as competition; deactivated users cannot join teams.

### Team

- Named group competing in **one competition**.
- Belongs to `Competition`; unique name per competition.
- Status flow: `forming` → `pending_approval` → `approved` | `rejected` (rejected may return to `forming`).
- Only relevant when `registration_mode` is `team` or `both`.

**Rules:** Min/max roster size from competition config; only `approved` teams eligible for Sprint 4 registration.

### Captain

- Exactly one per team; stored as `teams.captain_user_id` and `team_members.role = captain` (services keep both in sync).
- Can invite/remove members, submit for approval, transfer leadership.
- Cannot be removed without transferring captain role.

### Coach (P2)

- Optional `coach_user_id` on team; user must have `coach` role and same org.
- One coach per team in Sprint 3; coach may mentor multiple teams (allowed).

### Team membership (`team_members`)

- Pivot: `team_id`, `user_id`, `role` (`captain` | `member`), `status` (`active` | `removed`).
- Unique `(team_id, user_id)`; soft-remove via status for audit trail.

### Invitation (`team_invitations`)

- Captain (or organizer override) invites by email within org.
- Token + expiry (e.g. 7 days); statuses: pending, accepted, declined, revoked, expired.
- **Sprint 3:** no outbound email — database record only; UI inbox for invitee.
- **Deferred:** invite email outside org / account creation on accept → Sprint 4.

---

## Open questions — resolved

| Question | Resolution |
|---|---|
| Team per category or competition? | **Competition** — category chosen at registration (Sprint 4) |
| Separate Participant model? | **No** — User + profile |
| Guest invitations? | **Defer** to Sprint 4 |
| Who approves teams? | **Organizer** only (committee later) |
| Email notifications? | **Defer** — DB + UI only in Sprint 3 |
| Coach required? | Configurable per competition; feature P2 |
| Registration in Sprint 3? | **No** — eligibility services only, as handoff to Sprint 4 |
| `EffectiveCategoryConfig`? | **Defer** — eligibility at competition level in Sprint 3 |

---

## Edge cases (documented for design #23)

| Scenario | Handling |
|---|---|
| User already on another team in same competition | Block join/accept |
| Captain is only member | Allow team delete/disband |
| Captain deactivated | Organizer must reassign captain |
| Team at max capacity | Block invite and accept |
| Competition mode change after teams exist | Block mode change if teams exist (validation on competition update) |
| Competition closed | Teams read-only |
| Duplicate pending invite | Block second pending invite to same email |

---

## Relationship sketch

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

---

## Tenancy & authorization (preview)

- `OrganizationScope` on `Team` via `competition.organization_id` (same pattern as `CompetitionCategory`).
- Participant: manage own profile; create/manage teams as captain.
- Organizer: view all teams in org; approve/reject; override revoke invitations.
- Super admin: cross-org access.

Detailed policy matrix → Issue #23 (`TEAM_PARTICIPANT_DESIGN.md`).

---

## Sprint 3 success criteria

- Participant can complete profile, create team, invite org members, get team approved.
- Organizer can approve/reject teams.
- Eligibility services answer “can this user/team register?” with structured reasons (no HTTP routes yet).
- Full test coverage; zero cross-tenant leaks.
- Sprint 4 can add `Registration` model reusing Sprint 3 entities.

---

## Out of scope (explicit)

- `registrations` table and registration UI
- Payments, submissions, judging, certificates
- Full mail/notification pipeline
- Guest / external email invitations
- Committee role in approval workflow
- Team per category duplication
- `EffectiveCategoryConfig` resolver

---

## References

- [COMPETITION_DESIGN.md](COMPETITION_DESIGN.md) — Sprint 2 competition/category model
- [DECISIONS.md](DECISIONS.md) — ADR-0011–0016
- [ROADMAP.md](ROADMAP.md) — Sprint 3 checklist (issues #22–#40)
