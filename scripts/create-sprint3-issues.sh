#!/usr/bin/env bash
# One-time script to create Sprint 3 GitHub issues (#21–#39)
set -euo pipefail
cd "$(dirname "$0")/.."

create() {
  local title="$1"
  local body="$2"
  local labels="$3"
  gh issue create --title "$title" --body "$body" --label "$labels"
}

create "Sprint 3 domain research & decisions" "$(cat <<'EOF'
## Type
Research · Estimate: S · Priority: P0

## Epic
Research & Domain Design

## Objective
Research team/participant patterns and lock Sprint 3 domain decisions before schema work.

## Acceptance Criteria
- [ ] Open questions documented with recommendations
- [ ] Approved decisions: team per competition, one team per user per competition, org-only invites in Sprint 3
- [ ] Out-of-scope list explicit (no registrations table in Sprint 3)

## Definition of Done
- [ ] `docs/TEAM_PARTICIPANT_RESEARCH.md` committed
- [ ] User approval checkpoint before Issue #22

## Branch
`docs/s3-team-participant-research`

## Suggested Commit
`docs: research team and participant domain for Sprint 3`

## Dependencies
Sprint 2 complete

## Risks
Over-scoping guest invitations — defer to Sprint 4

## Learning Goals
Domain-driven design; bounded context between Sprint 3 and 4
EOF
)" "research,documentation"

create "Team & Participant design document + ADRs" "$(cat <<'EOF'
## Type
Documentation · Estimate: M · Priority: P0

## Epic
Research & Domain Design

## Objective
Author `docs/TEAM_PARTICIPANT_DESIGN.md`, add ADR-0017+, update `DATABASE.md` ERD.

## Acceptance Criteria
- [ ] ERD, enums, policy matrix, service list, HTTP surface draft
- [ ] Explicit out-of-scope for Sprint 3 documented

## Definition of Done
- [ ] Design doc merged; ADRs accepted; DATABASE.md updated

## Branch
`docs/s3-team-participant-design`

## Suggested Commit
`docs: add Team & Participant design and ADRs for Sprint 3`

## Dependencies
#21

## Risks
Doc drift — mark as living document

## Learning Goals
ADR practice; technical writing
EOF
)" "documentation,architecture"

create "Team & Participant foundation migrations, models, enums" "$(cat <<'EOF'
## Type
Feature · Estimate: M · Priority: P0

## Epic
Data Foundation

## Objective
Create `participant_profiles`, `teams`, `team_members`, `team_invitations` tables with models, enums, factories.

## Acceptance Criteria
- [ ] Migrations run cleanly
- [ ] Enums: TeamStatus, TeamMemberRole, InvitationStatus, RegistrationMode
- [ ] Factories for all new models

## Definition of Done
- [ ] `php artisan migrate` succeeds; factories usable in tests

## Branch
`feature/s3-team-participant-foundation`

## Suggested Commit
`feat(team): add Sprint 3 foundation migrations, models, and enums`

## Dependencies
#22

## Risks
Schema churn — lock design in #22 first
EOF
)" "backend,database"

create "Competition registration mode and team size settings" "$(cat <<'EOF'
## Type
Feature · Estimate: S · Priority: P1

## Epic
Data Foundation

## Objective
Extend competition create/update for `registration_mode`, `min_team_size`, `max_team_size`, `requires_coach`.

## Acceptance Criteria
- [ ] Form requests validate mode and sizes
- [ ] Only editable when competition is draft
- [ ] Organizer UI shows registration settings card

## Definition of Done
- [ ] Feature test for validation rules

## Branch
`feature/s3-competition-registration-mode`

## Suggested Commit
`feat(competition): add registration mode and team size settings`

## Dependencies
#23
EOF
)" "backend,frontend"

create "Team & Participant policies with unit tests" "$(cat <<'EOF'
## Type
Feature · Estimate: M · Priority: P0

## Epic
Authorization

## Objective
Add TeamPolicy, TeamInvitationPolicy, ParticipantProfilePolicy with unit tests.

## Acceptance Criteria
- [ ] Tenancy via competition organization
- [ ] Captain, organizer, participant, coach rules covered
- [ ] Cross-org access denied

## Definition of Done
- [ ] `tests/Unit/Policies/Team/` passing

## Branch
`feature/s3-team-policies`

## Suggested Commit
`feat(team): add policies and unit tests for team authorization`

## Dependencies
#23
EOF
)" "backend,testing"

create "Participant profile services and Form Requests" "$(cat <<'EOF'
## Type
Feature · Estimate: S · Priority: P1

## Epic
Participant Profile

## Objective
UpsertParticipantProfileService, ShowParticipantProfileService, Form Requests.

## Acceptance Criteria
- [ ] Participant upserts own profile
- [ ] Organizer can view profiles in org
- [ ] Minimal field set (bio, phone, institution)

## Definition of Done
- [ ] Unit tests for services

## Branch
`feature/s3-participant-profile-services`

## Suggested Commit
`feat(participant): add profile services and form requests`

## Dependencies
#25
EOF
)" "backend"

create "Participant profile UI" "$(cat <<'EOF'
## Type
Feature · Estimate: S · Priority: P1

## Epic
Participant Profile

## Objective
Inertia page for participant profile edit; sidebar nav for participants.

## Acceptance Criteria
- [ ] Participant can view/edit profile
- [ ] TypeScript types updated

## Definition of Done
- [ ] Feature test: participant can update profile

## Branch
`feature/s3-participant-profile-ui`

## Suggested Commit
`feat(participant): add participant profile UI`

## Dependencies
#26
EOF
)" "frontend"

create "Team CRUD services and Form Requests" "$(cat <<'EOF'
## Type
Feature · Estimate: M · Priority: P0

## Epic
Team Management

## Objective
CreateTeamService, UpdateTeamService, DeleteTeamService, ListTeamsService.

## Acceptance Criteria
- [ ] Participant creates team in team-enabled competition
- [ ] Unique name per competition
- [ ] Captain auto-assigned; status starts forming

## Definition of Done
- [ ] Unit tests; transaction creates team + captain membership

## Branch
`feature/s3-team-crud-services`

## Suggested Commit
`feat(team): add team CRUD services and form requests`

## Dependencies
#25, #24
EOF
)" "backend"

create "Captain transfer and member removal services" "$(cat <<'EOF'
## Type
Feature · Estimate: S · Priority: P0

## Epic
Team Management

## Objective
TransferCaptainService, RemoveTeamMemberService, LeaveTeamService.

## Acceptance Criteria
- [ ] Exactly one captain per team
- [ ] Member can leave; captain transfer atomic

## Definition of Done
- [ ] Unit tests for edge cases

## Branch
`feature/s3-team-membership-services`

## Suggested Commit
`feat(team): add captain transfer and member removal services`

## Dependencies
#28
EOF
)" "backend"

create "Participant team management UI" "$(cat <<'EOF'
## Type
Feature · Estimate: M · Priority: P1

## Epic
Team Management

## Objective
Inertia pages: team/teams Index, Create, Show for participants.

## Acceptance Criteria
- [ ] Create team from competition context
- [ ] View roster; captain actions by permission

## Definition of Done
- [ ] Feature tests for team HTTP flows

## Branch
`feature/s3-team-participant-ui`

## Suggested Commit
`feat(team): add participant team management UI`

## Dependencies
#28, #29
EOF
)" "frontend"

create "Team invitation services" "$(cat <<'EOF'
## Type
Feature · Estimate: M · Priority: P0

## Epic
Invitation System

## Objective
SendTeamInvitationService, RevokeTeamInvitationService with token and expiry.

## Acceptance Criteria
- [ ] Captain invites existing org user by email
- [ ] Team capacity checked; duplicate pending invite blocked
- [ ] No email delivery required (DB record only in Sprint 3)

## Definition of Done
- [ ] Unit tests

## Branch
`feature/s3-team-invitation-services`

## Suggested Commit
`feat(team): add team invitation services`

## Dependencies
#29
EOF
)" "backend"

create "Accept and decline team invitation services" "$(cat <<'EOF'
## Type
Feature · Estimate: S · Priority: P0

## Epic
Invitation System

## Objective
AcceptTeamInvitationService, DeclineTeamInvitationService.

## Acceptance Criteria
- [ ] Accept adds team_member row
- [ ] One team per user per competition enforced
- [ ] Expired/invalid token fails gracefully

## Definition of Done
- [ ] Unit and feature tests

## Branch
`feature/s3-team-invitation-accept`

## Suggested Commit
`feat(team): add accept and decline invitation services`

## Dependencies
#31
EOF
)" "backend"

create "Team invitation UI" "$(cat <<'EOF'
## Type
Feature · Estimate: M · Priority: P1

## Epic
Invitation System

## Objective
Captain invite form on team Show; participant pending invitations inbox.

## Acceptance Criteria
- [ ] End-to-end invite flow in UI for same-org users

## Definition of Done
- [ ] Feature test: captain invites, participant accepts

## Branch
`feature/s3-team-invitation-ui`

## Suggested Commit
`feat(team): add team invitation UI`

## Dependencies
#32
EOF
)" "frontend"

create "Team approval workflow services" "$(cat <<'EOF'
## Type
Feature · Estimate: S · Priority: P1

## Epic
Team Approval

## Objective
SubmitTeamForApprovalService, ApproveTeamService, RejectTeamService.

## Acceptance Criteria
- [ ] Status: forming → pending_approval → approved/rejected
- [ ] Validates min size and captain present

## Definition of Done
- [ ] Unit tests for invalid transitions

## Branch
`feature/s3-team-approval-services`

## Suggested Commit
`feat(team): add team approval workflow services`

## Dependencies
#29, #26
EOF
)" "backend"

create "Organizer team review UI" "$(cat <<'EOF'
## Type
Feature · Estimate: M · Priority: P1

## Epic
Team Approval

## Objective
Organizer UI to list pending teams per competition and approve/reject.

## Acceptance Criteria
- [ ] Organizer actions scoped to org
- [ ] Optional rejection reason field

## Definition of Done
- [ ] Feature tests

## Branch
`feature/s3-team-organizer-ui`

## Suggested Commit
`feat(team): add organizer team approval UI`

## Dependencies
#34
EOF
)" "frontend"

create "Optional coach assignment to team" "$(cat <<'EOF'
## Type
Feature · Estimate: S · Priority: P2

## Epic
Coach

## Objective
AssignCoachService, RemoveCoachService; respects requires_coach flag.

## Acceptance Criteria
- [ ] Coach must have coach role and same org
- [ ] One coach per team

## Definition of Done
- [ ] Unit tests; UI on team Show

## Branch
`feature/s3-team-coach-assignment`

## Suggested Commit
`feat(team): add optional coach assignment`

## Dependencies
#28
EOF
)" "backend"

create "Eligibility checker services for Sprint 4 registration" "$(cat <<'EOF'
## Type
Feature · Estimate: M · Priority: P0

## Epic
Validation & Eligibility

## Objective
CheckParticipantEligibilityService, CheckTeamEligibilityService — read-only, no registration table.

## Acceptance Criteria
- [ ] Returns eligible flag + reasons array
- [ ] Covers competition status, registration_mode, team approved, roster size
- [ ] Documented contract for Sprint 4

## Definition of Done
- [ ] Unit tests only; no HTTP routes

## Branch
`feature/s3-eligibility-services`

## Suggested Commit
`feat(team): add eligibility checker services for Sprint 4 registration`

## Dependencies
#34, #24
EOF
)" "backend"

create "Public page participation mode hints" "$(cat <<'EOF'
## Type
Feature · Estimate: S · Priority: P2

## Epic
Validation & Eligibility

## Objective
Show individual/team/both badges on public competition page.

## Acceptance Criteria
- [ ] Public page reflects registration_mode
- [ ] Copy does not promise registration before Sprint 4

## Definition of Done
- [ ] Feature test on public page props

## Branch
`feature/s3-public-page-participation-hints`

## Suggested Commit
`feat(team): add participation mode hints on public competition page`

## Dependencies
#24, #37
EOF
)" "frontend"

create "Sprint 3 Team module feature test consolidation" "$(cat <<'EOF'
## Type
Testing · Estimate: M · Priority: P0

## Epic
Testing

## Objective
Consolidate feature tests under `tests/Feature/Team/` with CreatesTeamFixtures trait.

## Acceptance Criteria
- [ ] CRUD, invitations, approval, tenancy, eligibility covered
- [ ] Full test suite green

## Definition of Done
- [ ] 15+ new feature tests

## Branch
`feature/s3-team-feature-tests`

## Suggested Commit
`test(team): consolidate Sprint 3 feature test coverage`

## Dependencies
All P0/P1 issues
EOF
)" "testing"

echo "Done creating Sprint 3 issues."
