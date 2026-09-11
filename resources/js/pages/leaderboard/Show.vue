<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { type LeaderboardCategory, type Organization } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Trophy } from 'lucide-vue-next';

interface PublicCompetition {
    id: number;
    name: string;
    slug: string;
}

interface Props {
    organization: Pick<Organization, 'id' | 'name' | 'slug'>;
    competition: PublicCompetition;
    categories: LeaderboardCategory[];
}

defineProps<Props>();

const formatScore = (score: number): string => score.toFixed(2);

const rankClass = (rank: number): string => {
    switch (rank) {
        case 1:
            return 'text-amber-600 dark:text-amber-400';
        case 2:
            return 'text-slate-500 dark:text-slate-400';
        case 3:
            return 'text-orange-700 dark:text-orange-400';
        default:
            return 'text-muted-foreground';
    }
};
</script>

<template>
    <Head :title="`Leaderboard — ${competition.name}`" />

    <div class="min-h-screen bg-background">
        <header class="border-b">
            <div class="mx-auto flex max-w-3xl items-center justify-between px-4 py-4">
                <div>
                    <p class="text-sm text-muted-foreground">{{ organization.name }}</p>
                    <h1 class="text-xl font-semibold">{{ competition.name }} — Leaderboard</h1>
                </div>
                <Link
                    :href="route('events.competitions.show', [organization.slug, competition.slug])"
                    class="text-sm text-muted-foreground hover:text-foreground"
                >
                    Back to event
                </Link>
            </div>
        </header>

        <main class="mx-auto flex max-w-3xl flex-col gap-6 p-4">
            <Card v-for="category in categories" :key="category.id">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Trophy class="h-5 w-5" />
                        {{ category.name }}
                    </CardTitle>
                </CardHeader>
                <CardContent class="p-0">
                    <div v-if="category.entries.length === 0" class="p-6 text-center text-sm text-muted-foreground">
                        No scored submissions in this category.
                    </div>
                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b text-left text-muted-foreground">
                                    <th class="px-6 py-3 font-medium">Rank</th>
                                    <th class="px-6 py-3 font-medium">Registrant</th>
                                    <th class="px-6 py-3 font-medium">Type</th>
                                    <th class="px-6 py-3 font-medium">Score</th>
                                    <th class="px-6 py-3 font-medium">Judges</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="entry in category.entries" :key="entry.rank" class="border-b last:border-0">
                                    <td class="px-6 py-4 font-semibold" :class="rankClass(entry.rank)">#{{ entry.rank }}</td>
                                    <td class="px-6 py-4 font-medium">{{ entry.registrant }}</td>
                                    <td class="px-6 py-4 text-muted-foreground">
                                        {{ entry.type === 'team' ? 'Team' : 'Individual' }}
                                    </td>
                                    <td class="px-6 py-4 text-muted-foreground">{{ formatScore(entry.aggregate_score) }}</td>
                                    <td class="px-6 py-4 text-muted-foreground">
                                        {{ entry.judge_count }} judge{{ entry.judge_count === 1 ? '' : 's' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>

            <p v-if="categories.length === 0" class="text-center text-sm text-muted-foreground">No categories to display.</p>
        </main>
    </div>
</template>
