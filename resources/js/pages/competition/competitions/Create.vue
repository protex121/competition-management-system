<script setup lang="ts">
import { TransitionRoot } from '@headlessui/vue';
import DateTimePicker from '@/components/DateTimePicker.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import RegistrationSettingsFields from '@/components/Competition/RegistrationSettingsFields.vue';
import { useTranslation } from '@/composables/useTranslation';
import { type BreadcrumbItem, type Organization } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    organizations: Organization[];
}

defineProps<Props>();

const { t } = useTranslation();
const page = usePage();
const isSuperAdmin = computed(() => page.props.auth.user?.role === 'super-admin');

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: t('nav.competitions'), href: route('competitions.index') },
    { title: t('competition.create_breadcrumb'), href: route('competitions.create') },
]);

const form = useForm({
    organization_id: '' as string | number,
    name: '',
    slug: '',
    description: '',
    starts_at: '',
    ends_at: '',
    registration_starts_at: '',
    registration_ends_at: '',
    max_participants: '' as string | number,
    registration_mode: 'individual',
    min_team_size: '' as string | number,
    max_team_size: '' as string | number,
    requires_coach: false,
});

const submit = () => {
    form.post(route('competitions.store'));
};
</script>

<template>
    <Head :title="t('competition.create_title')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">
            <Heading :title="t('competition.create_title')" :description="t('competition.create_description')" />

            <Card class="max-w-2xl">
                <CardHeader>
                    <CardTitle>{{ t('competition.competition_details') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-6">
                        <div v-if="isSuperAdmin" class="grid gap-2">
                            <Label for="organization_id">{{ t('competition.label_organization') }}</Label>
                            <select
                                id="organization_id"
                                v-model="form.organization_id"
                                required
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                            >
                                <option value="" disabled>{{ t('competition.select_organization') }}</option>
                                <option v-for="organization in organizations" :key="organization.id" :value="organization.id">
                                    {{ organization.name }}
                                </option>
                            </select>
                            <InputError :message="form.errors.organization_id" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="name">{{ t('competition.label_name') }}</Label>
                            <Input id="name" v-model="form.name" required :placeholder="t('competition.name_placeholder')" />
                            <InputError :message="form.errors.name" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="slug">{{ t('competition.label_slug') }} <span class="text-muted-foreground">{{ t('competition.optional') }}</span></Label>
                            <Input id="slug" v-model="form.slug" :placeholder="t('competition.slug_placeholder')" />
                            <p class="text-xs text-muted-foreground">{{ t('competition.slug_help') }}</p>
                            <InputError :message="form.errors.slug" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="description">{{ t('competition.label_description') }}</Label>
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="4"
                                class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                :placeholder="t('competition.description_placeholder')"
                            />
                            <InputError :message="form.errors.description" />
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="starts_at">{{ t('competition.label_starts_at') }}</Label>
                                <DateTimePicker id="starts_at" v-model="form.starts_at" />
                                <InputError :message="form.errors.starts_at" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="ends_at">{{ t('competition.label_ends_at') }}</Label>
                                <DateTimePicker id="ends_at" v-model="form.ends_at" />
                                <InputError :message="form.errors.ends_at" />
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="registration_starts_at">{{ t('competition.label_registration_starts_at') }}</Label>
                                <DateTimePicker id="registration_starts_at" v-model="form.registration_starts_at" />
                                <InputError :message="form.errors.registration_starts_at" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="registration_ends_at">{{ t('competition.label_registration_ends_at') }}</Label>
                                <DateTimePicker id="registration_ends_at" v-model="form.registration_ends_at" />
                                <InputError :message="form.errors.registration_ends_at" />
                            </div>
                        </div>

                        <div class="grid gap-2">
                            <Label for="max_participants">{{ t('competition.label_max_participants') }} <span class="text-muted-foreground">{{ t('competition.optional') }}</span></Label>
                            <Input id="max_participants" v-model="form.max_participants" type="number" min="1" :placeholder="t('competition.max_participants_placeholder')" />
                            <InputError :message="form.errors.max_participants" />
                        </div>

                        <div class="border-t pt-6">
                            <p class="mb-4 text-sm font-medium">{{ t('competition.registration_settings') }}</p>
                            <RegistrationSettingsFields
                                v-model:registration-mode="form.registration_mode"
                                v-model:min-team-size="form.min_team_size"
                                v-model:max-team-size="form.max_team_size"
                                v-model:requires-coach="form.requires_coach"
                                :errors="form.errors"
                            />
                        </div>

                        <div class="flex items-center gap-4">
                            <Button type="submit" :disabled="form.processing">
                                <LoaderCircle v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                                {{ t('competition.create_button') }}
                            </Button>
                            <Button as-child variant="outline">
                                <Link :href="route('competitions.index')">{{ t('common.cancel') }}</Link>
                            </Button>
                            <TransitionRoot
                                :show="form.recentlySuccessful"
                                enter="transition ease-in-out"
                                enter-from="opacity-0"
                                leave="transition ease-in-out"
                                leave-to="opacity-0"
                            >
                                <p class="text-sm text-muted-foreground">{{ t('common.created') }}</p>
                            </TransitionRoot>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
