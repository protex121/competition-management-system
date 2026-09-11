<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/composables/useTranslation';
import { type CompetitionCategory, type Organization } from '@/types';
import { Head, Link } from '@inertiajs/vue3';

interface PublicCompetition {
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
}

interface PublicCategory extends Pick<CompetitionCategory, 'id' | 'name' | 'slug'> {
    description: string | null;
    max_participants: number | null;
    registration_ends_at: string | null;
}

interface Props {
    organization: Pick<Organization, 'id' | 'name' | 'slug'>;
    competition: PublicCompetition;
    categories: PublicCategory[];
    participation: ParticipationCta;
}

interface ParticipationCta {
    visible: boolean;
    status?: string;
    message?: string;
    login_url?: string;
    register_url?: string;
    action_url?: string;
    action_label?: string;
}

defineProps<Props>();

const { t } = useTranslation();

const formatStatus = (status: string): string => t(`competition.status_${status}`);

const formatDate = (value: string | null): string => {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleString(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
};

const formatRegistrationMode = (mode: string): string => {
    switch (mode) {
        case 'team':
            return t('competition.registration_mode_team');
        case 'both':
            return t('competition.registration_mode_both');
        default:
            return t('competition.registration_mode_individual');
    }
};

const statusClass = (status: string): string => {
    switch (status) {
        case 'published':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300';
        case 'active':
            return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300';
        case 'closed':
            return 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-300';
        default:
            return 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300';
    }
};
</script>

<template>
    <Head :title="competition.name" />

    <div class="min-h-screen bg-background">
        <header class="border-b">
            <div class="mx-auto flex max-w-3xl items-center justify-between px-4 py-4">
                <div>
                    <p class="text-sm text-muted-foreground">{{ organization.name }}</p>
                    <h1 class="text-xl font-semibold">{{ competition.name }}</h1>
                </div>
                <div class="flex items-center gap-3">
                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium" :class="statusClass(competition.status)">
                        {{ formatStatus(competition.status) }}
                    </span>
                    <Link
                        v-if="$page.props.auth.user"
                        :href="route('dashboard')"
                        class="text-sm text-muted-foreground hover:text-foreground"
                    >
                        {{ t('nav.dashboard') }}
                    </Link>
                    <Link v-else :href="route('login')" class="text-sm text-muted-foreground hover:text-foreground"> {{ t('common.log_in') }} </Link>
                </div>
            </div>
        </header>

        <main class="mx-auto flex max-w-3xl flex-col gap-6 p-4">
            <Card v-if="participation.visible">
                <CardHeader>
                    <CardTitle>{{ t('competition.participate') }}</CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <p v-if="participation.message" class="text-sm text-muted-foreground">{{ participation.message }}</p>
                    <div class="flex flex-wrap gap-3">
                        <Button v-if="participation.action_url" as-child>
                            <Link :href="participation.action_url">{{ participation.action_label }}</Link>
                        </Button>
                        <template v-if="participation.status === 'guest'">
                            <Button v-if="participation.login_url" as-child variant="outline">
                                <Link :href="participation.login_url">{{ t('common.log_in') }}</Link>
                            </Button>
                            <Button v-if="participation.register_url" as-child variant="secondary">
                                <Link :href="participation.register_url">{{ t('competition.register') }}</Link>
                            </Button>
                        </template>
                    </div>
                </CardContent>
            </Card>

            <Card v-if="competition.status === 'closed'">
                <CardContent class="flex items-center justify-between py-4">
                    <p class="text-sm text-muted-foreground">{{ t('competition.closed_results_description') }}</p>
                    <Button as-child variant="outline" size="sm">
                        <Link :href="route('events.competitions.leaderboard', [organization.slug, competition.slug])">{{ t('competition.view_leaderboard') }}</Link>
                    </Button>
                </CardContent>
            </Card>

            <Card v-if="competition.description">
                <CardHeader>
                    <CardTitle>{{ t('competition.about') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="whitespace-pre-wrap text-sm text-muted-foreground">{{ competition.description }}</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>{{ t('competition.schedule') }}</CardTitle>
                </CardHeader>
                <CardContent class="grid gap-3 text-sm sm:grid-cols-2">
                    <div>
                        <p class="font-medium">{{ t('competition.event') }}</p>
                        <p class="text-muted-foreground">{{ formatDate(competition.starts_at) }} – {{ formatDate(competition.ends_at) }}</p>
                    </div>
                    <div>
                        <p class="font-medium">{{ t('competition.registration') }}</p>
                        <p class="text-muted-foreground">
                            {{ formatDate(competition.registration_starts_at) }} – {{ formatDate(competition.registration_ends_at) }}
                        </p>
                    </div>
                    <div>
                        <p class="font-medium">{{ t('competition.participation') }}</p>
                        <p class="text-muted-foreground">{{ formatRegistrationMode(competition.registration_mode) }}</p>
                        <p
                            v-if="competition.registration_mode === 'team' || competition.registration_mode === 'both'"
                            class="mt-1 text-muted-foreground"
                        >
                            {{ t('competition.team_size_label', { min: competition.min_team_size ?? '?', max: competition.max_team_size ?? '?' }) }}
                            <span v-if="competition.requires_coach"> · {{ t('competition.coach_required') }}</span>
                        </p>
                    </div>
                    <div v-if="competition.max_participants">
                        <p class="font-medium">{{ t('competition.capacity') }}</p>
                        <p class="text-muted-foreground">{{ t('competition.up_to_participants', { count: competition.max_participants }) }}</p>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>{{ competition.status === 'closed' ? t('competition.categories_archived') : t('competition.categories') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <ul v-if="categories.length" class="divide-y">
                        <li v-for="category in categories" :key="category.id" class="py-4 first:pt-0 last:pb-0">
                            <p class="font-medium">{{ category.name }}</p>
                            <p v-if="category.description" class="mt-1 text-sm text-muted-foreground">{{ category.description }}</p>
                            <div class="mt-2 flex flex-wrap gap-4 text-xs text-muted-foreground">
                                <span v-if="category.max_participants">{{ t('competition.category_max_participants', { count: category.max_participants }) }}</span>
                                <span v-if="category.registration_ends_at">
                                    {{ t('competition.category_registration_closes', { date: formatDate(category.registration_ends_at) }) }}
                                </span>
                            </div>
                        </li>
                    </ul>
                    <p v-else class="text-sm text-muted-foreground">
                        {{
                            competition.status === 'closed'
                                ? t('competition.no_category_info')
                                : t('competition.no_active_categories')
                        }}
                    </p>
                </CardContent>
            </Card>
        </main>
    </div>
</template>
