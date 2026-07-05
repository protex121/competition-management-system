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
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
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

export type BreadcrumbItemType = BreadcrumbItem;
