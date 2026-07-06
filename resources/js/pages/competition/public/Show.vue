<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
}

defineProps<Props>();

const formatStatus = (status: string): string =>
    status
        .split('-')
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');

const formatDate = (value: string | null): string => {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleString(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
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
                        Dashboard
                    </Link>
                    <Link v-else :href="route('login')" class="text-sm text-muted-foreground hover:text-foreground"> Log in </Link>
                </div>
            </div>
        </header>

        <main class="mx-auto flex max-w-3xl flex-col gap-6 p-4">
            <Card v-if="competition.description">
                <CardHeader>
                    <CardTitle>About</CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="whitespace-pre-wrap text-sm text-muted-foreground">{{ competition.description }}</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Schedule</CardTitle>
                </CardHeader>
                <CardContent class="grid gap-3 text-sm sm:grid-cols-2">
                    <div>
                        <p class="font-medium">Event</p>
                        <p class="text-muted-foreground">{{ formatDate(competition.starts_at) }} – {{ formatDate(competition.ends_at) }}</p>
                    </div>
                    <div>
                        <p class="font-medium">Registration</p>
                        <p class="text-muted-foreground">
                            {{ formatDate(competition.registration_starts_at) }} – {{ formatDate(competition.registration_ends_at) }}
                        </p>
                    </div>
                    <div v-if="competition.max_participants">
                        <p class="font-medium">Capacity</p>
                        <p class="text-muted-foreground">Up to {{ competition.max_participants }} participants</p>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>{{ competition.status === 'closed' ? 'Categories (archived)' : 'Categories' }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <ul v-if="categories.length" class="divide-y">
                        <li v-for="category in categories" :key="category.id" class="py-4 first:pt-0 last:pb-0">
                            <p class="font-medium">{{ category.name }}</p>
                            <p v-if="category.description" class="mt-1 text-sm text-muted-foreground">{{ category.description }}</p>
                            <div class="mt-2 flex flex-wrap gap-4 text-xs text-muted-foreground">
                                <span v-if="category.max_participants">Max {{ category.max_participants }} participants</span>
                                <span v-if="category.registration_ends_at">
                                    Registration closes {{ formatDate(category.registration_ends_at) }}
                                </span>
                            </div>
                        </li>
                    </ul>
                    <p v-else class="text-sm text-muted-foreground">
                        {{
                            competition.status === 'closed'
                                ? 'No category information available.'
                                : 'No active categories yet. Check back soon.'
                        }}
                    </p>
                </CardContent>
            </Card>
        </main>
    </div>
</template>
