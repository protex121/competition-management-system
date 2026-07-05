<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type Competition, type Organization, type PaginatedCompetitions } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    competitions: PaginatedCompetitions<Competition>;
}

defineProps<Props>();

const page = usePage();
const isSuperAdmin = computed(() => page.props.auth.user?.role === 'super-admin');

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Competitions', href: route('competitions.index') },
];

const formatStatus = (status: string): string =>
    status
        .split('-')
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');

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

const organizationName = (organization: Organization | null | undefined): string => organization?.name ?? '—';
</script>

<template>
    <Head title="Competitions" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">
            <div class="flex items-center justify-between">
                <Heading title="Competitions" description="Manage competitions in your organization" />
                <Button as-child>
                    <Link :href="route('competitions.create')">
                        <Plus class="mr-2 h-4 w-4" />
                        New competition
                    </Link>
                </Button>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>All competitions</CardTitle>
                </CardHeader>
                <CardContent class="p-0">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b text-left text-muted-foreground">
                                    <th class="px-6 py-3 font-medium">Name</th>
                                    <th class="px-6 py-3 font-medium">Slug</th>
                                    <th class="px-6 py-3 font-medium">Status</th>
                                    <th v-if="isSuperAdmin" class="px-6 py-3 font-medium">Organization</th>
                                    <th class="px-6 py-3 font-medium text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="competition in competitions.data" :key="competition.id" class="border-b last:border-0">
                                    <td class="px-6 py-4 font-medium">{{ competition.name }}</td>
                                    <td class="px-6 py-4 text-muted-foreground">{{ competition.slug }}</td>
                                    <td class="px-6 py-4">
                                        <span
                                            class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium"
                                            :class="statusClass(competition.status)"
                                        >
                                            {{ formatStatus(competition.status) }}
                                        </span>
                                    </td>
                                    <td v-if="isSuperAdmin" class="px-6 py-4 text-muted-foreground">
                                        {{ organizationName(competition.organization) }}
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <Button as-child variant="outline" size="sm">
                                            <Link :href="route('competitions.edit', competition.id)">Edit</Link>
                                        </Button>
                                    </td>
                                </tr>
                                <tr v-if="competitions.data.length === 0">
                                    <td :colspan="isSuperAdmin ? 5 : 4" class="px-6 py-8 text-center text-muted-foreground">
                                        No competitions yet. Create your first one.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-if="competitions.links.length > 3" class="flex items-center justify-center gap-1 border-t px-6 py-4">
                        <template v-for="(link, index) in competitions.links" :key="index">
                            <Button
                                v-if="link.url"
                                as-child
                                size="sm"
                                :variant="link.active ? 'default' : 'outline'"
                            >
                                <Link :href="link.url" preserve-scroll>
                                    <span v-html="link.label" />
                                </Link>
                            </Button>
                            <span v-else class="px-2 text-muted-foreground" v-html="link.label" />
                        </template>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
