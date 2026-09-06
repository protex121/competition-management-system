<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface SubmissionDetail {
    id: number;
    title: string;
    description: string | null;
    project_url: string | null;
    file_original_name: string | null;
    has_file: boolean;
    status: string;
    submitted_at: string | null;
    can: {
        update: boolean;
        finalize: boolean;
    };
}

interface Props {
    registration: {
        id: number;
        category: {
            id: number;
            name: string;
            competition: { id: number; name: string };
        };
    };
    submission: SubmissionDetail | null;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'My Registrations', href: route('registrations.index') },
    { title: 'Submission', href: route('registrations.submission.edit', props.registration.id) },
];

const isLocked = computed(() => props.submission?.status === 'finalized');

const form = useForm({
    title: props.submission?.title ?? '',
    description: props.submission?.description ?? '',
    project_url: props.submission?.project_url ?? '',
});

const submit = () => {
    form.put(route('registrations.submission.update', props.registration.id), {
        preserveScroll: true,
    });
};

const fileInput = ref<HTMLInputElement | null>(null);

const fileForm = useForm<{ file: File | null }>({
    file: null,
});

const onFileSelected = (event: Event) => {
    fileForm.file = (event.target as HTMLInputElement).files?.[0] ?? null;
};

const uploadFile = () => {
    if (!props.submission) {
        return;
    }

    fileForm.post(route('submissions.file.store', props.submission.id), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            fileForm.reset('file');

            if (fileInput.value) {
                fileInput.value.value = '';
            }
        },
    });
};

const finalize = () => {
    if (!props.submission) {
        return;
    }

    if (!confirm('Finalize this submission? You will not be able to edit it afterwards.')) {
        return;
    }

    router.post(route('submissions.finalize', props.submission.id));
};

const formatStatus = (status: string): string => status.charAt(0).toUpperCase() + status.slice(1);

const statusClass = (status: string): string => {
    switch (status) {
        case 'finalized':
            return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300';
        default:
            return 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300';
    }
};
</script>

<template>
    <Head title="Submission" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">
            <div class="flex items-start justify-between gap-4">
                <Heading
                    title="Submission"
                    :description="`${registration.category.competition.name} · ${registration.category.name}`"
                    :show-separator="false"
                />
                <span
                    v-if="submission"
                    class="inline-flex shrink-0 rounded-full px-2.5 py-0.5 text-xs font-medium"
                    :class="statusClass(submission.status)"
                >
                    {{ formatStatus(submission.status) }}
                </span>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle>Details</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form class="space-y-4" @submit.prevent="submit">
                            <div class="grid gap-2">
                                <Label for="title">Title</Label>
                                <Input id="title" v-model="form.title" required :disabled="isLocked" />
                                <InputError :message="form.errors.title" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="description">Description</Label>
                                <textarea
                                    id="description"
                                    v-model="form.description"
                                    rows="5"
                                    :disabled="isLocked"
                                    class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                                />
                                <InputError :message="form.errors.description" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="project_url">Project link (repo, demo, etc.)</Label>
                                <Input
                                    id="project_url"
                                    v-model="form.project_url"
                                    type="url"
                                    placeholder="https://github.com/..."
                                    :disabled="isLocked"
                                />
                                <InputError :message="form.errors.project_url" />
                            </div>
                            <InputError :message="form.errors.submission" />
                            <Button v-if="!isLocked" type="submit" :disabled="form.processing">
                                <LoaderCircle v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                                Save
                            </Button>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>File</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <p v-if="submission?.file_original_name" class="text-sm">
                            <a :href="route('submissions.file.download', submission.id)" class="font-medium text-primary hover:underline">
                                {{ submission.file_original_name }}
                            </a>
                        </p>
                        <p v-else class="text-sm text-muted-foreground">No file uploaded yet.</p>

                        <form v-if="submission && !isLocked" class="flex gap-2" @submit.prevent="uploadFile">
                            <div class="grid flex-1 gap-2">
                                <Label for="file" class="sr-only">File</Label>
                                <input
                                    id="file"
                                    ref="fileInput"
                                    type="file"
                                    accept=".zip,.pdf,.doc,.docx"
                                    class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm file:mr-2 file:rounded file:border-0 file:bg-muted file:px-2 file:py-1"
                                    @change="onFileSelected"
                                />
                                <InputError :message="fileForm.errors.file" />
                            </div>
                            <Button type="submit" :disabled="!fileForm.file || fileForm.processing">Upload</Button>
                        </form>
                        <p v-else-if="!submission" class="text-xs text-muted-foreground">Save the details first to attach a file.</p>
                    </CardContent>
                </Card>
            </div>

            <div v-if="submission?.can.finalize" class="flex items-center gap-4">
                <Button @click="finalize">Finalize submission</Button>
                <p class="text-sm text-muted-foreground">Locks the title, description, link, and file.</p>
            </div>
        </div>
    </AppLayout>
</template>
