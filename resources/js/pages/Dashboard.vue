<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { useTranslation } from '@/composables/useTranslation';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Mail, Trophy, UserCircle } from 'lucide-vue-next';
import { computed } from 'vue';

const { t } = useTranslation();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    {
        title: t('dashboard.title'),
        href: route('dashboard'),
    },
]);

const page = usePage<SharedData>();

const isParticipant = computed(() => page.props.auth.user?.role === 'participant');
const pendingInvitations = computed(() => page.props.pendingInvitationsCount ?? 0);
</script>

<template>
    <Head :title="t('dashboard.title')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <template v-if="isParticipant">
                <div class="grid gap-4 md:grid-cols-3">
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2 text-base">
                                <Trophy class="h-4 w-4" />
                                {{ t('dashboard.competitions') }}
                            </CardTitle>
                            <CardDescription>{{ t('dashboard.competitions_description') }}</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Button as-child>
                                <Link :href="route('participant.competitions.index')">{{ t('dashboard.browse_competitions') }}</Link>
                            </Button>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2 text-base">
                                <Mail class="h-4 w-4" />
                                {{ t('dashboard.team_invitations') }}
                                <span
                                    v-if="pendingInvitations > 0"
                                    class="rounded-full bg-primary px-2 py-0.5 text-xs font-medium text-primary-foreground"
                                >
                                    {{ pendingInvitations }}
                                </span>
                            </CardTitle>
                            <CardDescription>{{ t('dashboard.team_invitations_description') }}</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Button as-child variant="outline">
                                <Link :href="route('invitations.index')">{{ t('dashboard.view_invitations') }}</Link>
                            </Button>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2 text-base">
                                <UserCircle class="h-4 w-4" />
                                {{ t('dashboard.profile') }}
                            </CardTitle>
                            <CardDescription>{{ t('dashboard.profile_description') }}</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Button as-child variant="outline">
                                <Link :href="route('participant.profile.edit')">{{ t('dashboard.edit_profile') }}</Link>
                            </Button>
                        </CardContent>
                    </Card>
                </div>
            </template>
            <template v-else>
                <div class="grid auto-rows-min gap-4 md:grid-cols-3">
                    <div class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                        <div class="flex h-full items-center justify-center text-sm text-muted-foreground">{{ t('dashboard.coming_soon') }}</div>
                    </div>
                    <div class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                        <div class="flex h-full items-center justify-center text-sm text-muted-foreground">{{ t('dashboard.coming_soon') }}</div>
                    </div>
                    <div class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                        <div class="flex h-full items-center justify-center text-sm text-muted-foreground">{{ t('dashboard.coming_soon') }}</div>
                    </div>
                </div>
            </template>
        </div>
    </AppLayout>
</template>
