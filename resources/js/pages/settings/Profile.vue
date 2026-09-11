<script setup lang="ts">
import { TransitionRoot } from '@headlessui/vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import DeleteUser from '@/components/DeleteUser.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslation } from '@/composables/useTranslation';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem, type SharedData, type User } from '@/types';

const { t } = useTranslation();

interface Props {
    mustVerifyEmail: boolean;
    status?: string;
    className?: string;
}

defineProps<Props>();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    {
        title: t('settings_ui.profile_title'),
        href: route('profile.edit'),
    },
]);

const page = usePage<SharedData>();
const user = page.props.auth.user as User;

const form = useForm({
    name: user.name,
    email: user.email,
});

const submit = () => {
    form.patch(route('profile.update'), {
        preserveScroll: true,
    });
};

const avatarPreview = ref<string | null>(user.avatar_url ?? null);

const avatarForm = useForm<{ avatar: File | null }>({
    avatar: null,
});

const onAvatarSelected = (event: Event) => {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;
    avatarForm.avatar = file;

    if (file) {
        avatarPreview.value = URL.createObjectURL(file);
    }
};

const uploadAvatar = () => {
    avatarForm.post(route('profile.avatar.update'), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => avatarForm.reset('avatar'),
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="t('settings_ui.profile_title')" />

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <HeadingSmall :title="t('settings_ui.avatar_title')" :description="t('settings_ui.avatar_description')" />

                <div class="flex items-center gap-4">
                    <span class="inline-flex h-16 w-16 items-center justify-center overflow-hidden rounded-full bg-neutral-200">
                        <img v-if="avatarPreview" :src="avatarPreview" alt="Avatar preview" class="h-full w-full object-cover" />
                        <span v-else class="text-lg font-medium text-neutral-500">{{ user.name.charAt(0).toUpperCase() }}</span>
                    </span>

                    <div class="grid gap-2">
                        <Input id="avatar" type="file" accept="image/jpeg,image/png,image/webp" @change="onAvatarSelected" />
                        <InputError :message="avatarForm.errors.avatar" />
                    </div>

                    <Button type="button" :disabled="!avatarForm.avatar || avatarForm.processing" @click="uploadAvatar">
                        {{ t('common.upload') }}
                    </Button>
                </div>

                <HeadingSmall :title="t('settings_ui.profile_info_title')" :description="t('settings_ui.profile_info_description')" />

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid gap-2">
                        <Label for="name">{{ t('settings_ui.name') }}</Label>
                        <Input
                            id="name"
                            class="mt-1 block w-full"
                            v-model="form.name"
                            required
                            autocomplete="name"
                            :placeholder="t('settings_ui.full_name_placeholder')"
                        />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="email">{{ t('auth_ui.email_address') }}</Label>
                        <Input
                            id="email"
                            type="email"
                            class="mt-1 block w-full"
                            v-model="form.email"
                            required
                            autocomplete="username"
                            :placeholder="t('settings_ui.email_placeholder')"
                        />
                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>

                    <div v-if="mustVerifyEmail && !user.email_verified_at">
                        <p class="mt-2 text-sm text-neutral-800">
                            {{ t('settings_ui.email_unverified') }}
                            <Link
                                :href="route('verification.send')"
                                method="post"
                                as="button"
                                class="focus:outline-hidden rounded-md text-sm text-neutral-600 underline hover:text-neutral-900 focus:ring-2 focus:ring-offset-2"
                            >
                                {{ t('settings_ui.resend_verification_link') }}
                            </Link>
                        </p>

                        <div v-if="status === 'verification-link-sent'" class="mt-2 text-sm font-medium text-green-600">
                            {{ t('settings_ui.verification_link_sent') }}
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <Button :disabled="form.processing">{{ t('common.save') }}</Button>

                        <TransitionRoot
                            :show="form.recentlySuccessful"
                            enter="transition ease-in-out"
                            enter-from="opacity-0"
                            leave="transition ease-in-out"
                            leave-to="opacity-0"
                        >
                            <p class="text-sm text-neutral-600">{{ t('common.saved') }}</p>
                        </TransitionRoot>
                    </div>
                </form>
            </div>

            <DeleteUser />
        </SettingsLayout>
    </AppLayout>
</template>
