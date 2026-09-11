<script setup lang="ts">
import { TransitionRoot } from '@headlessui/vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type Organization, type RoleOption } from '@/types';
import { useTranslation } from '@/composables/useTranslation';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    roles: RoleOption[];
    organizations: Organization[];
}

const props = defineProps<Props>();

const { t } = useTranslation();
const page = usePage();
const isSuperAdmin = computed(() => page.props.auth.user?.role === 'super-admin');

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: t('nav.users'), href: route('users.index') },
    { title: t('identity.create_title'), href: route('users.create') },
]);

const form = useForm({
    organization_id: '' as string | number,
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: props.roles[0]?.value ?? '',
});

const submit = () => {
    form.post(route('users.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head :title="t('identity.create_title')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">
            <Heading :title="t('identity.create_title')" :description="t('identity.create_description')" />

            <Card class="max-w-2xl">
                <CardHeader>
                    <CardTitle>{{ t('identity.user_details') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-6">
                        <div v-if="isSuperAdmin" class="grid gap-2">
                            <Label for="organization_id">{{ t('identity.label_organization') }}</Label>
                            <select
                                id="organization_id"
                                v-model="form.organization_id"
                                required
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                            >
                                <option value="" disabled>{{ t('identity.select_organization') }}</option>
                                <option v-for="organization in organizations" :key="organization.id" :value="organization.id">
                                    {{ organization.name }}
                                </option>
                            </select>
                            <InputError :message="form.errors.organization_id" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="name">{{ t('identity.label_name') }}</Label>
                            <Input id="name" v-model="form.name" required autocomplete="name" :placeholder="t('identity.full_name_placeholder')" />
                            <InputError :message="form.errors.name" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="email">{{ t('identity.label_email') }}</Label>
                            <Input id="email" type="email" v-model="form.email" required autocomplete="email" placeholder="email@example.com" />
                            <InputError :message="form.errors.email" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="role">{{ t('identity.label_role') }}</Label>
                            <select
                                id="role"
                                v-model="form.role"
                                required
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                            >
                                <option v-for="role in roles" :key="role.value" :value="role.value">
                                    {{ role.label }}
                                </option>
                            </select>
                            <InputError :message="form.errors.role" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="password">{{ t('identity.label_password') }}</Label>
                            <Input id="password" type="password" v-model="form.password" required autocomplete="new-password" />
                            <InputError :message="form.errors.password" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="password_confirmation">{{ t('identity.label_password_confirmation') }}</Label>
                            <Input
                                id="password_confirmation"
                                type="password"
                                v-model="form.password_confirmation"
                                required
                                autocomplete="new-password"
                            />
                            <InputError :message="form.errors.password_confirmation" />
                        </div>

                        <div class="flex items-center gap-4">
                            <Button type="submit" :disabled="form.processing">
                                <LoaderCircle v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                                {{ t('identity.create_user_button') }}
                            </Button>
                            <Button as-child variant="outline">
                                <Link :href="route('users.index')">{{ t('common.cancel') }}</Link>
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
