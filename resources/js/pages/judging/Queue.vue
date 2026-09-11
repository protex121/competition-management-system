<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Gavel } from 'lucide-vue-next';

interface QueueItem {
    id: number;
    title: string;
    submitted_at: string | null;
    registrant: string | null;
    type: 'individual' | 'team';
    competition: { id: number; name: string };
    category: { id: number; name: string };
    has_scored: boolean;
}

interface Props {
    submissions: QueueItem[];
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Judging Queue', href: route('judging.queue.index') }];

const formatDate = (value: string | null): string => {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' });
};
</script>

<template>
    <Head title="Judging Queue" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">
            <Heading title="Judging Queue" description="Finalized submissions in the competitions you're assigned to" />

            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Gavel class="h-5 w-5" />
                        Submissions ({{ submissions.length }})
                    </CardTitle>
                </CardHeader>
                <CardContent class="p-0">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b text-left text-muted-foreground">
                                    <th class="px-6 py-3 font-medium">Competition</th>
                                    <th class="px-6 py-3 font-medium">Category</th>
                                    <th class="px-6 py-3 font-medium">Title</th>
                                    <th class="px-6 py-3 font-medium">Registrant</th>
                                    <th class="px-6 py-3 font-medium">Submitted</th>
                                    <th class="px-6 py-3 font-medium">Status</th>
                                    <th class="px-6 py-3 text-right font-medium">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="submission in submissions" :key="submission.id" class="border-b last:border-0">
                                    <td class="px-6 py-4 font-medium">{{ submission.competition.name }}</td>
                                    <td class="px-6 py-4 text-muted-foreground">{{ submission.category.name }}</td>
                                    <td class="px-6 py-4 text-muted-foreground">{{ submission.title }}</td>
                                    <td class="px-6 py-4 text-muted-foreground">
                                        {{ submission.registrant }}
                                        <span class="text-xs">({{ submission.type === 'team' ? 'Team' : 'Individual' }})</span>
                                    </td>
                                    <td class="px-6 py-4 text-muted-foreground">{{ formatDate(submission.submitted_at) }}</td>
                                    <td class="px-6 py-4">
                                        <span
                                            class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium"
                                            :class="
                                                submission.has_scored
                                                    ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300'
                                                    : 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300'
                                            "
                                        >
                                            {{ submission.has_scored ? 'Scored' : 'Pending' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <Button as-child variant="outline" size="sm">
                                            <Link :href="route('submissions.score.edit', submission.id)">
                                                {{ submission.has_scored ? 'Rescore' : 'Score' }}
                                            </Link>
                                        </Button>
                                    </td>
                                </tr>
                                <tr v-if="submissions.length === 0">
                                    <td colspan="7" class="px-6 py-8 text-center text-muted-foreground">Nothing to score right now.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
