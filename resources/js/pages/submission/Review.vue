<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { FileText } from 'lucide-vue-next';

interface SubmissionItem {
    id: number;
    title: string;
    file_original_name: string | null;
    has_file: boolean;
    status: string;
    submitted_at: string | null;
    registrant: string | null;
    type: 'individual' | 'team';
}

interface Props {
    competition: { id: number; name: string };
    category: { id: number; name: string };
    submissions: SubmissionItem[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Competitions', href: route('competitions.index') },
    { title: props.competition.name, href: route('competitions.edit', props.competition.id) },
    {
        title: `${props.category.name} submissions`,
        href: route('competitions.categories.submissions.index', [props.competition.id, props.category.id]),
    },
];

const formatStatus = (status: string): string => status.charAt(0).toUpperCase() + status.slice(1);

const statusClass = (status: string): string => {
    switch (status) {
        case 'finalized':
            return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300';
        default:
            return 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300';
    }
};

const formatDate = (value: string | null): string => {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' });
};
</script>

<template>
    <Head :title="`Submissions — ${category.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">
            <Heading :title="`Submissions — ${category.name}`" :description="`${competition.name} · read-only oversight`" />

            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <FileText class="h-5 w-5" />
                        Submitted ({{ submissions.length }})
                    </CardTitle>
                </CardHeader>
                <CardContent class="p-0">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b text-left text-muted-foreground">
                                    <th class="px-6 py-3 font-medium">Registrant</th>
                                    <th class="px-6 py-3 font-medium">Type</th>
                                    <th class="px-6 py-3 font-medium">Title</th>
                                    <th class="px-6 py-3 font-medium">Status</th>
                                    <th class="px-6 py-3 font-medium">Submitted</th>
                                    <th class="px-6 py-3 text-right font-medium">File</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="submission in submissions" :key="submission.id" class="border-b last:border-0">
                                    <td class="px-6 py-4 font-medium">{{ submission.registrant }}</td>
                                    <td class="px-6 py-4 text-muted-foreground">
                                        {{ submission.type === 'team' ? 'Team' : 'Individual' }}
                                    </td>
                                    <td class="px-6 py-4 text-muted-foreground">{{ submission.title }}</td>
                                    <td class="px-6 py-4">
                                        <span
                                            class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium"
                                            :class="statusClass(submission.status)"
                                        >
                                            {{ formatStatus(submission.status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-muted-foreground">{{ formatDate(submission.submitted_at) }}</td>
                                    <td class="px-6 py-4 text-right">
                                        <a
                                            v-if="submission.has_file"
                                            :href="route('submissions.file.download', submission.id)"
                                            class="font-medium text-primary hover:underline"
                                        >
                                            {{ submission.file_original_name }}
                                        </a>
                                        <span v-else class="text-xs text-muted-foreground">—</span>
                                    </td>
                                </tr>
                                <tr v-if="submissions.length === 0">
                                    <td colspan="6" class="px-6 py-8 text-center text-muted-foreground">No submissions yet.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
