<script setup lang="ts">
import { TransitionRoot } from '@headlessui/vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import RegistrationSettingsFields from '@/components/Competition/RegistrationSettingsFields.vue';
import { type BreadcrumbItem, type Organization } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    organizations: Organization[];
}

defineProps<Props>();

const page = usePage();
const isSuperAdmin = computed(() => page.props.auth.user?.role === 'super-admin');

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Competitions', href: route('competitions.index') },
    { title: 'Create', href: route('competitions.create') },
];

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
    <Head title="Create competition" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">
            <Heading title="Create competition" description="Set up a new competition for your organization" />

            <Card class="max-w-2xl">
                <CardHeader>
                    <CardTitle>Competition details</CardTitle>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-6">
                        <div v-if="isSuperAdmin" class="grid gap-2">
                            <Label for="organization_id">Organization</Label>
                            <select
                                id="organization_id"
                                v-model="form.organization_id"
                                required
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                            >
                                <option value="" disabled>Select organization</option>
                                <option v-for="organization in organizations" :key="organization.id" :value="organization.id">
                                    {{ organization.name }}
                                </option>
                            </select>
                            <InputError :message="form.errors.organization_id" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="name">Name</Label>
                            <Input id="name" v-model="form.name" required placeholder="Summer Hackathon 2026" />
                            <InputError :message="form.errors.name" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="slug">Slug <span class="text-muted-foreground">(optional)</span></Label>
                            <Input id="slug" v-model="form.slug" placeholder="summer-hackathon-2026" />
                            <p class="text-xs text-muted-foreground">Leave blank to auto-generate from the name.</p>
                            <InputError :message="form.errors.slug" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="description">Description</Label>
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="4"
                                class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                placeholder="Brief description of the event"
                            />
                            <InputError :message="form.errors.description" />
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="starts_at">Event starts</Label>
                                <Input id="starts_at" v-model="form.starts_at" type="datetime-local" />
                                <InputError :message="form.errors.starts_at" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="ends_at">Event ends</Label>
                                <Input id="ends_at" v-model="form.ends_at" type="datetime-local" />
                                <InputError :message="form.errors.ends_at" />
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="registration_starts_at">Registration opens</Label>
                                <Input id="registration_starts_at" v-model="form.registration_starts_at" type="datetime-local" />
                                <InputError :message="form.errors.registration_starts_at" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="registration_ends_at">Registration closes</Label>
                                <Input id="registration_ends_at" v-model="form.registration_ends_at" type="datetime-local" />
                                <InputError :message="form.errors.registration_ends_at" />
                            </div>
                        </div>

                        <div class="grid gap-2">
                            <Label for="max_participants">Max participants <span class="text-muted-foreground">(optional)</span></Label>
                            <Input id="max_participants" v-model="form.max_participants" type="number" min="1" placeholder="100" />
                            <InputError :message="form.errors.max_participants" />
                        </div>

                        <div class="border-t pt-6">
                            <p class="mb-4 text-sm font-medium">Registration settings</p>
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
                                Create competition
                            </Button>
                            <Button as-child variant="outline">
                                <Link :href="route('competitions.index')">Cancel</Link>
                            </Button>
                            <TransitionRoot
                                :show="form.recentlySuccessful"
                                enter="transition ease-in-out"
                                enter-from="opacity-0"
                                leave="transition ease-in-out"
                                leave-to="opacity-0"
                            >
                                <p class="text-sm text-muted-foreground">Created.</p>
                            </TransitionRoot>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
