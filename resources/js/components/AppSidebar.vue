<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { useTranslation } from '@/composables/useTranslation';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { BookOpen, ClipboardList, Folder, Gavel, LayoutGrid, Mail, Trophy, UserCircle, Users } from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from './AppLogo.vue';

const page = usePage<SharedData>();
const { t } = useTranslation();

const canManageUsers = computed(() => {
    const role = page.props.auth.user?.role;

    return role === 'organizer' || role === 'super-admin';
});

const canManageCompetitions = computed(() => {
    const role = page.props.auth.user?.role;

    return role === 'organizer' || role === 'super-admin';
});

const isParticipant = computed(() => page.props.auth.user?.role === 'participant');

const isJudge = computed(() => page.props.auth.user?.role === 'judge');

const pendingInvitationsCount = computed(() => page.props.pendingInvitationsCount ?? 0);

const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        {
            title: t('nav.dashboard'),
            href: route('dashboard'),
            icon: LayoutGrid,
        },
    ];

    if (isParticipant.value) {
        items.push({
            title: t('nav.competitions'),
            href: route('participant.competitions.index'),
            icon: Trophy,
        });
        items.push({
            title: t('nav.invitations'),
            href: route('invitations.index'),
            icon: Mail,
            badge: pendingInvitationsCount.value,
        });
        items.push({
            title: t('nav.my_registrations'),
            href: route('registrations.index'),
            icon: ClipboardList,
        });
        items.push({
            title: t('nav.my_profile'),
            href: route('participant.profile.edit'),
            icon: UserCircle,
        });
    }

    if (isJudge.value) {
        items.push({
            title: t('nav.judging_queue'),
            href: route('judging.queue.index'),
            icon: Gavel,
        });
    }

    if (canManageUsers.value) {
        items.push({
            title: t('nav.users'),
            href: route('users.index'),
            icon: Users,
        });
    }

    if (canManageCompetitions.value) {
        items.push({
            title: t('nav.competitions'),
            href: route('competitions.index'),
            icon: Trophy,
        });
    }

    return items;
});

const footerNavItems = computed<NavItem[]>(() => [
    {
        title: t('nav.github_repo'),
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: Folder,
    },
    {
        title: t('nav.documentation'),
        href: 'https://laravel.com/docs/starter-kits',
        icon: BookOpen,
    },
]);
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('dashboard')">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
