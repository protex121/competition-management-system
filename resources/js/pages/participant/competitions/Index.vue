<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Trophy } from 'lucide-vue-next';

interface ParticipantTeamSummary {
    id: number;
    name: string;
    status: string;
}

interface BrowseCompetition {
    id: number;
    name: string;
    slug: string;
    status: string;
    registration_mode: string;
    allows_teams: boolean;
    starts_at: string | null;
    registration_ends_at: string | null;
    my_team: ParticipantTeamSummary | null;
}

interface Props {
    competitions: {
        data: BrowseCompetition[];
    };
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Competitions', href: route('participant.competitions.index') },
];

const formatStatus = (status: string): string =>
    status
        .split('_')
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');

const formatMode = (mode: string): string => {
    switch (mode) {
        case 'team':
            return 'Team';
        case 'both':
            return 'Individual & Team';
        default:
            return 'Individual';
    }
};

const formatDate = (value: string | null): string => {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleDateString(undefined, { dateStyle: 'medium' });
};
</script>

<template>
    <Head title="Competitions" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">
            <Heading
                title="Competitions"
                description="Browse open competitions in your organization and manage your teams"
            />

            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Trophy class="h-5 w-5" />
                        Open for participation
                    </CardTitle>
                </CardHeader>
                <CardContent class="p-0">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b text-left text-muted-foreground">
                                    <th class="px-6 py-3 font-medium">Name</th>
                                    <th class="px-6 py-3 font-medium">Mode</th>
                                    <th class="px-6 py-3 font-medium">Starts</th>
                                    <th class="px-6 py-3 font-medium">Your team</th>
                                    <th class="px-6 py-3 font-medium text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="competition in competitions.data" :key="competition.id" class="border-b last:border-0">
                                    <td class="px-6 py-4 font-medium">{{ competition.name }}</td>
                                    <td class="px-6 py-4 text-muted-foreground">{{ formatMode(competition.registration_mode) }}</td>
                                    <td class="px-6 py-4 text-muted-foreground">{{ formatDate(competition.starts_at) }}</td>
                                    <td class="px-6 py-4 text-muted-foreground">
                                        <template v-if="competition.my_team">
                                            {{ competition.my_team.name }}
                                            <span class="text-xs">({{ formatStatus(competition.my_team.status) }})</span>
                                        </template>
                                        <template v-else-if="competition.allows_teams">—</template>
                                        <template v-else>N/A</template>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <template v-if="competition.allows_teams">
                                            <Button v-if="competition.my_team" as-child variant="outline" size="sm">
                                                <Link :href="route('teams.show', competition.my_team.id)">View team</Link>
                                            </Button>
                                            <Button v-else as-child size="sm">
                                                <Link :href="route('competitions.teams.index', competition.id)">Join / create team</Link>
                                            </Button>
                                        </template>
                                        <span v-else class="text-xs text-muted-foreground">Individual only</span>
                                    </td>
                                </tr>
                                <tr v-if="competitions.data.length === 0">
                                    <td colspan="5" class="px-6 py-8 text-center text-muted-foreground">
                                        No open competitions right now. Check back later.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
