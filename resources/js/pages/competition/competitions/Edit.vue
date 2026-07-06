<script setup lang="ts">
import { TransitionRoot } from '@headlessui/vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import CategoryList from '@/components/Competition/CategoryList.vue';
import RegistrationSettingsFields from '@/components/Competition/RegistrationSettingsFields.vue';
import { type BreadcrumbItem, type Competition, type CompetitionPermissions, type ManagedCategory } from '@/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    competition: Competition;
    categories: ManagedCategory[];
    can: CompetitionPermissions;
}

const props = defineProps<Props>();

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success as string | undefined);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Competitions', href: route('competitions.index') },
    { title: props.competition.name, href: route('competitions.edit', props.competition.id) },
];

const isDraft = computed(() => props.competition.status === 'draft');

const toDatetimeLocal = (value: string | null): string => (value ? value.slice(0, 16) : '');

const form = useForm({
    name: props.competition.name,
    slug: props.competition.slug,
    description: props.competition.description ?? '',
    starts_at: toDatetimeLocal(props.competition.starts_at),
    ends_at: toDatetimeLocal(props.competition.ends_at),
    registration_starts_at: toDatetimeLocal(props.competition.registration_starts_at),
    registration_ends_at: toDatetimeLocal(props.competition.registration_ends_at),
    max_participants: props.competition.max_participants ?? ('' as string | number),
    registration_mode: props.competition.registration_mode ?? 'individual',
    min_team_size: props.competition.min_team_size ?? ('' as string | number),
    max_team_size: props.competition.max_team_size ?? ('' as string | number),
    requires_coach: props.competition.requires_coach ?? false,
});

const submit = () => {
    if (!isDraft.value) {
        form.transform((data) => {
            const rest = { ...data };
            delete rest.registration_mode;
            delete rest.min_team_size;
            delete rest.max_team_size;
            delete rest.requires_coach;

            return rest;
        });
    }

    form.put(route('competitions.update', props.competition.id), {
        preserveScroll: true,
    });
};

const publish = () => {
    router.patch(route('competitions.publish', props.competition.id), {}, { preserveScroll: true });
};

const activate = () => {
    router.patch(route('competitions.activate', props.competition.id), {}, { preserveScroll: true });
};

const close = () => {
    router.patch(route('competitions.close', props.competition.id), {}, { preserveScroll: true });
};

const deleteForm = useForm({});

const deleteCompetition = () => {
    deleteForm.delete(route('competitions.destroy', props.competition.id));
};

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
</script>

<template>
    <Head :title="`Edit ${competition.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">
            <div class="flex items-start justify-between gap-4">
                <Heading :title="competition.name" description="Update competition details and manage lifecycle" />
                <span class="inline-flex shrink-0 rounded-full px-2.5 py-0.5 text-xs font-medium" :class="statusClass(competition.status)">
                    {{ formatStatus(competition.status) }}
                </span>
            </div>

            <p v-if="flashSuccess" class="text-sm text-green-600 dark:text-green-400">{{ flashSuccess }}</p>
            <InputError :message="form.errors.status" />

            <form @submit.prevent="submit" class="flex max-w-2xl flex-col gap-6">
                <Card>
                    <CardHeader>
                        <CardTitle>Details</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <div class="grid gap-2">
                            <Label for="name">Name</Label>
                            <Input id="name" v-model="form.name" required :disabled="!can.update" />
                            <InputError :message="form.errors.name" />
                        </div>

                        <div v-if="isDraft" class="grid gap-2">
                            <Label for="slug">Slug</Label>
                            <Input id="slug" v-model="form.slug" required :disabled="!can.update" />
                            <InputError :message="form.errors.slug" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="description">Description</Label>
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="4"
                                :disabled="!can.update"
                                class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                            />
                            <InputError :message="form.errors.description" />
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="starts_at">Event starts</Label>
                                <Input id="starts_at" v-model="form.starts_at" type="datetime-local" :disabled="!can.update" />
                                <InputError :message="form.errors.starts_at" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="ends_at">Event ends</Label>
                                <Input id="ends_at" v-model="form.ends_at" type="datetime-local" :disabled="!can.update" />
                                <InputError :message="form.errors.ends_at" />
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="registration_starts_at">Registration opens</Label>
                                <Input
                                    id="registration_starts_at"
                                    v-model="form.registration_starts_at"
                                    type="datetime-local"
                                    :disabled="!can.update"
                                />
                                <InputError :message="form.errors.registration_starts_at" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="registration_ends_at">Registration closes</Label>
                                <Input
                                    id="registration_ends_at"
                                    v-model="form.registration_ends_at"
                                    type="datetime-local"
                                    :disabled="!can.update"
                                />
                                <InputError :message="form.errors.registration_ends_at" />
                            </div>
                        </div>

                        <div class="grid gap-2">
                            <Label for="max_participants">Max participants</Label>
                            <Input
                                id="max_participants"
                                v-model="form.max_participants"
                                type="number"
                                min="1"
                                :disabled="!can.update"
                            />
                            <InputError :message="form.errors.max_participants" />
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Registration settings</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p v-if="!isDraft" class="mb-4 text-sm text-muted-foreground">
                            Participation mode and team size can only be changed while the competition is in draft status.
                        </p>
                        <RegistrationSettingsFields
                            v-model:registration-mode="form.registration_mode"
                            v-model:min-team-size="form.min_team_size"
                            v-model:max-team-size="form.max_team_size"
                            v-model:requires-coach="form.requires_coach"
                            :errors="form.errors"
                            :disabled="!isDraft || !can.update"
                        />
                    </CardContent>
                </Card>

                <div v-if="can.update" class="flex items-center gap-4">
                    <Button type="submit" :disabled="form.processing">
                        <LoaderCircle v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                        Save changes
                    </Button>
                    <Button as-child variant="outline">
                        <Link :href="route('competitions.index')">Back to list</Link>
                    </Button>
                    <TransitionRoot
                        :show="form.recentlySuccessful"
                        enter="transition ease-in-out"
                        enter-from="opacity-0"
                        leave="transition ease-in-out"
                        leave-to="opacity-0"
                    >
                        <p class="text-sm text-muted-foreground">Saved.</p>
                    </TransitionRoot>
                </div>
            </form>

            <Card class="max-w-2xl">
                <CardHeader>
                    <CardTitle>Categories</CardTitle>
                </CardHeader>
                <CardContent>
                    <CategoryList
                        :competition-id="competition.id"
                        :categories="categories"
                        :can-create="can.createCategory"
                    />
                </CardContent>
            </Card>

            <Card v-if="can.publish || can.activate || can.close" class="max-w-2xl">
                <CardHeader>
                    <CardTitle>Lifecycle</CardTitle>
                </CardHeader>
                <CardContent class="flex flex-wrap gap-3">
                    <Button v-if="can.publish" type="button" @click="publish">Publish</Button>
                    <Button v-if="can.activate" type="button" @click="activate">Activate</Button>
                    <Button v-if="can.close" type="button" variant="outline" @click="close">Close</Button>
                </CardContent>
            </Card>

            <Card v-if="can.delete" class="max-w-2xl border-red-200 dark:border-red-900/50">
                <CardHeader>
                    <CardTitle class="text-red-600 dark:text-red-400">Danger zone</CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <p class="text-sm text-muted-foreground">Only draft competitions can be deleted.</p>
                    <Dialog>
                        <DialogTrigger as-child>
                            <Button variant="destructive">Delete competition</Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>Delete {{ competition.name }}?</DialogTitle>
                                <DialogDescription>
                                    This will soft-delete the competition. This action cannot be undone from the UI.
                                </DialogDescription>
                            </DialogHeader>
                            <DialogFooter>
                                <DialogClose as-child>
                                    <Button variant="secondary">Cancel</Button>
                                </DialogClose>
                                <Button variant="destructive" :disabled="deleteForm.processing" @click="deleteCompetition">
                                    <LoaderCircle v-if="deleteForm.processing" class="mr-2 h-4 w-4 animate-spin" />
                                    Delete
                                </Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
