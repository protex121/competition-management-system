import type { LucideIcon } from 'lucide-vue-next';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon;
    isActive?: boolean;
    badge?: number;
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    pendingInvitationsCount?: number;
    ziggy: {
        location: string;
        url: string;
        port: null | number;
        defaults: Record<string, unknown>;
        routes: Record<string, string>;
    };
}

export interface User {
    id: number;
    name: string;
    email: string;
    role: string;
    avatar?: string;
    avatar_url?: string | null;
    deactivated_at?: string | null;
    organization?: Organization | null;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
}

export interface ManagedUser extends User {
    role: string;
}

export interface Organization {
    id: number;
    name: string;
}

export interface RoleOption {
    value: string;
    label: string;
}

export interface UserPermissions {
    deactivate: boolean;
    reactivate: boolean;
    delete: boolean;
}

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface PaginatedUsers<T> {
    data: T[];
    links: PaginationLink[];
    meta?: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
}

export interface CompetitionCategory {
    id: number;
    name: string;
    slug: string;
    description?: string | null;
    status: string;
    is_default: boolean;
    sort_order: number;
    max_participants?: number | null;
    registration_ends_at?: string | null;
}

export interface CategoryPermissions {
    update: boolean;
    delete: boolean;
    activate: boolean;
    disable: boolean;
}

export interface ManagedCategory extends CompetitionCategory {
    can: CategoryPermissions;
}

export interface Competition {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    status: string;
    starts_at: string | null;
    ends_at: string | null;
    registration_starts_at: string | null;
    registration_ends_at: string | null;
    max_participants: number | null;
    registration_mode: string;
    min_team_size: number | null;
    max_team_size: number | null;
    requires_coach: boolean;
    organization_id: number;
    organization?: Organization | null;
    categories?: CompetitionCategory[];
    created_at: string;
    updated_at: string;
}

export interface CompetitionPermissions {
    update: boolean;
    delete: boolean;
    publish: boolean;
    activate: boolean;
    close: boolean;
    createCategory: boolean;
}

export interface PaginatedCompetitions<T> {
    data: T[];
    links: PaginationLink[];
    meta?: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
}

export interface TeamMemberUser {
    id: number;
    name: string;
    email: string;
}

export interface TeamMemberSummary {
    id: number;
    role: string;
    user: TeamMemberUser;
    can?: {
        transferCaptain: boolean;
        remove: boolean;
    };
}

export interface TeamCaptain {
    id: number;
    name: string;
}

export interface TeamSummary {
    id: number;
    name: string;
    status: string;
    captain_user_id?: number;
    captain?: TeamCaptain | null;
    members?: TeamMemberSummary[];
}

export interface TeamInvitationSummary {
    id: number;
    email: string;
    expires_at: string;
    can?: {
        revoke: boolean;
    };
}

export interface TeamDetail extends TeamSummary {
    rejection_reason: string | null;
    submitted_at: string | null;
    approved_at: string | null;
    competition: {
        id: number;
        name: string;
    };
    members: TeamMemberSummary[];
    pending_invitations: TeamInvitationSummary[];
}

export interface TeamPermissions {
    update: boolean;
    delete: boolean;
    manageMembers: boolean;
    invite: boolean;
    submit: boolean;
    leave: boolean;
}

export interface PaginatedTeams<T> {
    data: T[];
    links: PaginationLink[];
    meta?: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
}

export type BreadcrumbItemType = BreadcrumbItem;
