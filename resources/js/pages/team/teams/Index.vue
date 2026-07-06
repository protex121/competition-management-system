<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type PaginatedTeams, type TeamSummary } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Plus, Users } from 'lucide-vue-next';

interface CompetitionContext {
    id: number;
    name: string;
    slug: string;
    status: string;
    registration_mode: string;
    min_team_size: number | null;
    max_team_size: number | null;
}

interface Props {
    competition: CompetitionContext;
    teams: PaginatedTeams<TeamSummary>;
    can: {
        create: boolean;
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Teams', href: route('competitions.teams.index', props.competition.id) },
];

const formatStatus = (status: string): string =>
    status
        .split('_')
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');

const statusClass = (status: string): string => {
    switch (status) {
        case 'approved':
            return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300';
        case 'pending_approval':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300';
        case 'rejected':
            return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300';
        default:
            return 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300';
    }
};

const memberCount = (team: TeamSummary): number => team.members?.length ?? 0;
</script>

<template>
    <Head :title="`Teams — ${competition.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">
            <div class="mb-8">
                <div class="flex items-start justify-between gap-4">
                    <Heading
                        :title="`Teams for ${competition.name}`"
                        description="View and manage teams in this competition"
                        :show-separator="false"
                    />
                    <Button v-if="can.create" as-child class="shrink-0">
                        <Link :href="route('competitions.teams.create', competition.id)">
                            <Plus class="mr-2 h-4 w-4" />
                            Create team
                        </Link>
                    </Button>
                </div>
                <Separator class="mt-6" />
            </div>

            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Users class="h-5 w-5" />
                        Teams
                    </CardTitle>
                </CardHeader>
                <CardContent class="p-0">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b text-left text-muted-foreground">
                                    <th class="px-6 py-3 font-medium">Name</th>
                                    <th class="px-6 py-3 font-medium">Captain</th>
                                    <th class="px-6 py-3 font-medium">Members</th>
                                    <th class="px-6 py-3 font-medium">Status</th>
                                    <th class="px-6 py-3 font-medium text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="team in teams.data" :key="team.id" class="border-b last:border-0">
                                    <td class="px-6 py-4 font-medium">{{ team.name }}</td>
                                    <td class="px-6 py-4 text-muted-foreground">{{ team.captain?.name ?? '—' }}</td>
                                    <td class="px-6 py-4 text-muted-foreground">{{ memberCount(team) }}</td>
                                    <td class="px-6 py-4">
                                        <span
                                            class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium"
                                            :class="statusClass(team.status)"
                                        >
                                            {{ formatStatus(team.status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <Button as-child variant="outline" size="sm">
                                            <Link :href="route('teams.show', team.id)">View</Link>
                                        </Button>
                                    </td>
                                </tr>
                                <tr v-if="teams.data.length === 0">
                                    <td colspan="5" class="px-6 py-8 text-center text-muted-foreground">
                                        <template v-if="can.create">No teams yet. Create one to get started.</template>
                                        <template v-else>No teams to show.</template>
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
