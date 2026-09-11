<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

interface CriterionEntry {
    id: number;
    name: string;
    description: string | null;
    max_score: number;
    score: number | null;
    comment: string | null;
}

interface Props {
    submission: {
        id: number;
        title: string;
        description: string | null;
        project_url: string | null;
        file_original_name: string | null;
        has_file: boolean;
        registrant: string | null;
        type: 'individual' | 'team';
    };
    competition: { id: number; name: string };
    criteria: CriterionEntry[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Judging Queue', href: route('judging.queue.index') },
    { title: props.submission.title, href: route('submissions.score.edit', props.submission.id) },
];

const form = useForm({
    entries: props.criteria.map((criterion) => ({
        rubric_criterion_id: criterion.id,
        score: criterion.score ?? ('' as number | string),
        comment: criterion.comment ?? '',
    })),
});

const submit = () => {
    form.put(route('submissions.score.update', props.submission.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="`Score — ${submission.title}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">
            <Heading :title="submission.title" :description="`${competition.name} · ${submission.registrant} (${submission.type})`" />

            <div class="grid gap-6 lg:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle>Submission</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-3 text-sm">
                        <p v-if="submission.description">{{ submission.description }}</p>
                        <p v-else class="text-muted-foreground">No description provided.</p>
                        <p v-if="submission.project_url">
                            <a :href="submission.project_url" target="_blank" rel="noopener" class="font-medium text-primary hover:underline">
                                {{ submission.project_url }}
                            </a>
                        </p>
                        <p v-if="submission.has_file">
                            <a :href="route('submissions.file.download', submission.id)" class="font-medium text-primary hover:underline">
                                {{ submission.file_original_name }}
                            </a>
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Scorecard</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form class="space-y-6" @submit.prevent="submit">
                            <div v-for="(criterion, index) in criteria" :key="criterion.id" class="space-y-2 border-b pb-4 last:border-0 last:pb-0">
                                <div class="flex items-baseline justify-between gap-2">
                                    <Label :for="`score-${criterion.id}`">{{ criterion.name }}</Label>
                                    <span class="text-xs text-muted-foreground">max {{ criterion.max_score }}</span>
                                </div>
                                <p v-if="criterion.description" class="text-xs text-muted-foreground">{{ criterion.description }}</p>
                                <Input
                                    :id="`score-${criterion.id}`"
                                    v-model="form.entries[index].score"
                                    type="number"
                                    min="0"
                                    :max="criterion.max_score"
                                    required
                                />
                                <InputError :message="(form.errors as Record<string, string>)[`entries.${index}.score`]" />
                                <textarea
                                    v-model="form.entries[index].comment"
                                    rows="2"
                                    placeholder="Comment (optional)"
                                    class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                />
                            </div>

                            <InputError :message="form.errors.score" />

                            <Button type="submit" :disabled="form.processing">
                                <LoaderCircle v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                                Save scorecard
                            </Button>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
