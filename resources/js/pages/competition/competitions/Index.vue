<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type Competition, type Organization, type PaginatedCompetitions } from '@/types';
import { useTranslation } from '@/composables/useTranslation';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    competitions: PaginatedCompetitions<Competition>;
}

defineProps<Props>();

const { t } = useTranslation();
const page = usePage();
const isSuperAdmin = computed(() => page.props.auth.user?.role === 'super-admin');

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: t('nav.competitions'), href: route('competitions.index') },
]);

const formatStatus = (status: string): string => t(`competition.status_${status}`);

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
    <Head :title="t('nav.competitions')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">
            <div class="mb-8">
                <div class="flex items-start justify-between gap-4">
                    <Heading :title="t('nav.competitions')" :description="t('competition.index_description')" :show-separator="false" />
                    <Button as-child class="shrink-0">
                        <Link :href="route('competitions.create')">
                            <Plus class="mr-2 h-4 w-4" />
                            {{ t('competition.new_competition') }}
                        </Link>
                    </Button>
                </div>
                <Separator class="mt-6" />
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>{{ t('competition.all_competitions') }}</CardTitle>
                </CardHeader>
                <CardContent class="p-0">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b text-left text-muted-foreground">
                                    <th class="px-6 py-3 font-medium">{{ t('competition.th_name') }}</th>
                                    <th class="px-6 py-3 font-medium">{{ t('competition.th_slug') }}</th>
                                    <th class="px-6 py-3 font-medium">{{ t('competition.th_status') }}</th>
                                    <th v-if="isSuperAdmin" class="px-6 py-3 font-medium">{{ t('competition.th_organization') }}</th>
                                    <th class="px-6 py-3 font-medium text-right">{{ t('competition.th_actions') }}</th>
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
                                            <Link :href="route('competitions.edit', competition.id)">{{ t('common.edit') }}</Link>
                                        </Button>
                                    </td>
                                </tr>
                                <tr v-if="competitions.data.length === 0">
                                    <td :colspan="isSuperAdmin ? 5 : 4" class="px-6 py-8 text-center text-muted-foreground">
                                        {{ t('competition.empty_competitions') }}
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
