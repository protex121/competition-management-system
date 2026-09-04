<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Trophy } from 'lucide-vue-next';
import { reactive } from 'vue';

interface ParticipantTeamSummary {
    id: number;
    name: string;
    status: string;
}

interface CategoryOption {
    id: number;
    name: string;
}

interface ParticipantRegistrationSummary {
    id: number;
    status: string;
    category_name: string;
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
    categories: CategoryOption[];
    my_registration: ParticipantRegistrationSummary | null;
}

interface Props {
    competitions: {
        data: BrowseCompetition[];
    };
}

const props = defineProps<Props>();

const selectedCategory = reactive<Record<number, number | ''>>({});

for (const competition of props.competitions.data) {
    selectedCategory[competition.id] = competition.categories[0]?.id ?? '';
}

const register = (competition: BrowseCompetition) => {
    const categoryId = selectedCategory[competition.id];

    if (!categoryId) {
        return;
    }

    router.post(route('competitions.registrations.store', competition.id), { competition_category_id: categoryId }, { preserveScroll: true });
};

const withdraw = (registration: ParticipantRegistrationSummary) => {
    if (!confirm('Withdraw this registration? This frees your slot for someone else.')) {
        return;
    }

    router.patch(route('registrations.withdraw', registration.id), {}, { preserveScroll: true });
};

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Competitions', href: route('participant.competitions.index') }];

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
            <Heading title="Competitions" description="Browse open competitions in your organization and manage your teams" />

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
                                    <th class="px-6 py-3 font-medium">Your registration</th>
                                    <th class="px-6 py-3 text-right font-medium">Actions</th>
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
                                    <td class="px-6 py-4 text-muted-foreground">
                                        <template v-if="competition.registration_mode === 'team'">N/A</template>
                                        <template v-else-if="competition.my_registration">
                                            {{ competition.my_registration.category_name }}
                                            <span class="text-xs">({{ formatStatus(competition.my_registration.status) }})</span>
                                        </template>
                                        <template v-else>—</template>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex flex-wrap items-center justify-end gap-2">
                                            <template v-if="competition.allows_teams">
                                                <Button v-if="competition.my_team" as-child variant="outline" size="sm">
                                                    <Link :href="route('teams.show', competition.my_team.id)">View team</Link>
                                                </Button>
                                                <Button v-else as-child size="sm">
                                                    <Link :href="route('competitions.teams.index', competition.id)">Join / create team</Link>
                                                </Button>
                                            </template>

                                            <template v-if="competition.registration_mode !== 'team'">
                                                <Button
                                                    v-if="competition.my_registration"
                                                    variant="outline"
                                                    size="sm"
                                                    @click="withdraw(competition.my_registration)"
                                                >
                                                    Withdraw
                                                </Button>
                                                <template v-else-if="competition.categories.length > 0">
                                                    <select
                                                        v-if="competition.categories.length > 1"
                                                        v-model="selectedCategory[competition.id]"
                                                        class="h-8 rounded-md border border-input bg-background px-2 text-xs"
                                                    >
                                                        <option v-for="category in competition.categories" :key="category.id" :value="category.id">
                                                            {{ category.name }}
                                                        </option>
                                                    </select>
                                                    <Button size="sm" @click="register(competition)">Register</Button>
                                                </template>
                                                <span v-else class="text-xs text-muted-foreground">No categories open</span>
                                            </template>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="competitions.data.length === 0">
                                    <td colspan="6" class="px-6 py-8 text-center text-muted-foreground">
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
