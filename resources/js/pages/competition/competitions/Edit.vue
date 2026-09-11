<script setup lang="ts">
import CategoryList from '@/components/Competition/CategoryList.vue';
import RegistrationSettingsFields from '@/components/Competition/RegistrationSettingsFields.vue';
import DateTimePicker from '@/components/DateTimePicker.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import JudgesList from '@/components/Judging/JudgesList.vue';
import RubricCriteriaList from '@/components/Judging/RubricCriteriaList.vue';
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
import {
    type BreadcrumbItem,
    type Competition,
    type CompetitionJudgeAssignment,
    type CompetitionPermissions,
    type ManagedCategory,
    type RubricCriterionItem,
} from '@/types';
import { useTranslation } from '@/composables/useTranslation';
import { TransitionRoot } from '@headlessui/vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import { computed } from 'vue';

interface JudgeOption {
    id: number;
    name: string;
    email: string;
}

interface Props {
    competition: Competition;
    categories: ManagedCategory[];
    judges: CompetitionJudgeAssignment[];
    availableJudges: JudgeOption[];
    rubricCriteria: RubricCriterionItem[];
    can: CompetitionPermissions;
}

const props = defineProps<Props>();

const { t } = useTranslation();
const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success as string | undefined);

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: t('nav.competitions'), href: route('competitions.index') },
    { title: props.competition.name, href: route('competitions.edit', props.competition.id) },
]);

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
</script>

<template>
    <Head :title="`${t('common.edit')} ${competition.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">
            <div class="flex items-start justify-between gap-4">
                <Heading :title="competition.name" :description="t('competition.edit_description')" />
                <span class="inline-flex shrink-0 rounded-full px-2.5 py-0.5 text-xs font-medium" :class="statusClass(competition.status)">
                    {{ formatStatus(competition.status) }}
                </span>
            </div>

            <p v-if="flashSuccess" class="text-sm text-green-600 dark:text-green-400">{{ flashSuccess }}</p>
            <InputError :message="form.errors.status" />

            <form @submit.prevent="submit" class="flex max-w-2xl flex-col gap-6">
                <Card>
                    <CardHeader>
                        <CardTitle>{{ t('competition.details') }}</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <div class="grid gap-2">
                            <Label for="name">{{ t('competition.label_name') }}</Label>
                            <Input id="name" v-model="form.name" required :disabled="!can.update" />
                            <InputError :message="form.errors.name" />
                        </div>

                        <div v-if="isDraft" class="grid gap-2">
                            <Label for="slug">{{ t('competition.label_slug') }}</Label>
                            <Input id="slug" v-model="form.slug" required :disabled="!can.update" />
                            <InputError :message="form.errors.slug" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="description">{{ t('competition.label_description') }}</Label>
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
                                <Label for="starts_at">{{ t('competition.label_starts_at') }}</Label>
                                <DateTimePicker id="starts_at" v-model="form.starts_at" :disabled="!can.update" />
                                <InputError :message="form.errors.starts_at" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="ends_at">{{ t('competition.label_ends_at') }}</Label>
                                <DateTimePicker id="ends_at" v-model="form.ends_at" :disabled="!can.update" />
                                <InputError :message="form.errors.ends_at" />
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="registration_starts_at">{{ t('competition.label_registration_starts_at') }}</Label>
                                <DateTimePicker id="registration_starts_at" v-model="form.registration_starts_at" :disabled="!can.update" />
                                <InputError :message="form.errors.registration_starts_at" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="registration_ends_at">{{ t('competition.label_registration_ends_at') }}</Label>
                                <DateTimePicker id="registration_ends_at" v-model="form.registration_ends_at" :disabled="!can.update" />
                                <InputError :message="form.errors.registration_ends_at" />
                            </div>
                        </div>

                        <div class="grid gap-2">
                            <Label for="max_participants">{{ t('competition.label_max_participants') }}</Label>
                            <Input id="max_participants" v-model="form.max_participants" type="number" min="1" :disabled="!can.update" />
                            <InputError :message="form.errors.max_participants" />
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>{{ t('competition.registration_settings') }}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p v-if="!isDraft" class="mb-4 text-sm text-muted-foreground">
                            {{ t('competition.registration_settings_locked') }}
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
                        {{ t('common.save_changes') }}
                    </Button>
                    <Button as-child variant="outline">
                        <Link :href="route('competitions.index')">{{ t('common.back_to_list') }}</Link>
                    </Button>
                    <TransitionRoot
                        :show="form.recentlySuccessful"
                        enter="transition ease-in-out"
                        enter-from="opacity-0"
                        leave="transition ease-in-out"
                        leave-to="opacity-0"
                    >
                        <p class="text-sm text-muted-foreground">{{ t('common.saved') }}</p>
                    </TransitionRoot>
                </div>
            </form>

            <Card class="max-w-2xl">
                <CardHeader>
                    <CardTitle>{{ t('competition.categories') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <CategoryList :competition-id="competition.id" :categories="categories" :can-create="can.createCategory" />
                </CardContent>
            </Card>

            <Card v-if="judges.length > 0 || can.manageJudges" class="max-w-2xl">
                <CardHeader>
                    <CardTitle>{{ t('competition.judges') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <JudgesList
                        :competition-id="competition.id"
                        :judges="judges"
                        :available-judges="availableJudges"
                        :can-manage="can.manageJudges"
                    />
                </CardContent>
            </Card>

            <Card v-if="rubricCriteria.length > 0 || can.createRubricCriterion" class="max-w-2xl">
                <CardHeader>
                    <CardTitle>{{ t('competition.rubric') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <RubricCriteriaList :competition-id="competition.id" :criteria="rubricCriteria" :can-create="can.createRubricCriterion" />
                </CardContent>
            </Card>

            <Card v-if="competition.status === 'closed' && competition.organization" class="max-w-2xl">
                <CardHeader>
                    <CardTitle>{{ t('competition.leaderboard') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="mb-4 text-sm text-muted-foreground">{{ t('competition.leaderboard_closed_description') }}</p>
                    <Button as-child variant="outline">
                        <Link :href="route('events.competitions.leaderboard', [competition.organization.slug, competition.slug])">
                            {{ t('competition.view_leaderboard') }}
                        </Link>
                    </Button>
                </CardContent>
            </Card>

            <Card v-if="can.reviewTeams" class="max-w-2xl">
                <CardHeader>
                    <CardTitle>{{ t('competition.teams') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="mb-4 text-sm text-muted-foreground">{{ t('competition.teams_review_description') }}</p>
                    <Button as-child variant="outline">
                        <Link :href="route('competitions.teams.review', competition.id)">{{ t('competition.review_pending_teams') }}</Link>
                    </Button>
                </CardContent>
            </Card>

            <Card v-if="can.publish || can.activate || can.close" class="max-w-2xl">
                <CardHeader>
                    <CardTitle>{{ t('competition.lifecycle') }}</CardTitle>
                </CardHeader>
                <CardContent class="flex flex-wrap gap-3">
                    <Button v-if="can.publish" type="button" @click="publish">{{ t('competition.publish') }}</Button>
                    <Button v-if="can.activate" type="button" @click="activate">{{ t('competition.activate') }}</Button>
                    <Button v-if="can.close" type="button" variant="outline" @click="close">{{ t('competition.close') }}</Button>
                </CardContent>
            </Card>

            <Card v-if="can.delete" class="max-w-2xl border-red-200 dark:border-red-900/50">
                <CardHeader>
                    <CardTitle class="text-red-600 dark:text-red-400">{{ t('competition.danger_zone') }}</CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <p class="text-sm text-muted-foreground">{{ t('competition.delete_competition_description') }}</p>
                    <Dialog>
                        <DialogTrigger as-child>
                            <Button variant="destructive">{{ t('competition.delete_competition') }}</Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>{{ t('competition.delete_competition_dialog_title', { name: competition.name }) }}</DialogTitle>
                                <DialogDescription>
                                    {{ t('competition.delete_competition_dialog_description') }}
                                </DialogDescription>
                            </DialogHeader>
                            <DialogFooter>
                                <DialogClose as-child>
                                    <Button variant="secondary">{{ t('common.cancel') }}</Button>
                                </DialogClose>
                                <Button variant="destructive" :disabled="deleteForm.processing" @click="deleteCompetition">
                                    <LoaderCircle v-if="deleteForm.processing" class="mr-2 h-4 w-4 animate-spin" />
                                    {{ t('common.delete') }}
                                </Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
