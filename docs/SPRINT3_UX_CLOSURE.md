# Sprint 3 UX Closure

Closes the gap between implemented backend services/routes and discoverable UI flows.

## Gap inventory

| ID | Gap | Design reference | Backend | UI |
|----|-----|------------------|---------|-----|
| UX-1 | Participant cannot discover competitions or teams | HTTP surface | Routes exist | No browse page, no sidebar links |
| UX-2 | Invitation inbox not in navigation | `GET /invitations` | Done | No sidebar link / badge |
| UX-3 | Dashboard is placeholder for all roles | — | — | No actionable links |
| UX-4 | Team membership actions missing | `transfer-captain`, `DELETE member` | Services done | No routes/UI |
| UX-5 | Coach assignment missing | `AssignCoachService` | Services done | No routes/UI |
| UX-6 | Organizer team review not linked from competition edit | `teams/review` | Done | No button on Edit page |
| UX-7 | Public page has no participation CTA | — | Hints done | No login/join link |

## Implementation order

1. **#62** ✅ — Participant competition browse + navigation — merged PR #67
2. **#63** ✅ — Team membership UI — merged PR #68
3. **#64** ✅ — Coach assignment UI — merged PR #69
4. **#65** — Organizer review link on competition edit
5. **#66** — Public page participation CTA

## Out of scope

- Sprint 4 `registrations` table
- Email notifications for invitations
- Full participant dashboard analytics
