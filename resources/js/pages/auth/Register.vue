<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslation } from '@/composables/useTranslation';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

const { t } = useTranslation();

const form = useForm({
    organization_name: '',
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <AuthBase :title="t('auth_ui.register_title')" :description="t('auth_ui.register_description')">
        <Head :title="t('common.sign_up')" />

        <form @submit.prevent="submit" class="flex flex-col gap-6">
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="organization_name">{{ t('auth_ui.organization_name') }}</Label>
                    <Input
                        id="organization_name"
                        type="text"
                        required
                        autofocus
                        tabindex="1"
                        autocomplete="organization"
                        v-model="form.organization_name"
                        placeholder="Acme Hackathons"
                    />
                    <InputError :message="form.errors.organization_name" />
                </div>

                <div class="grid gap-2">
                    <Label for="name">{{ t('auth_ui.your_name') }}</Label>
                    <Input
                        id="name"
                        type="text"
                        required
                        tabindex="2"
                        autocomplete="name"
                        v-model="form.name"
                        :placeholder="t('settings_ui.full_name_placeholder')"
                    />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="email">{{ t('auth_ui.email_address') }}</Label>
                    <Input id="email" type="email" required tabindex="3" autocomplete="email" v-model="form.email" placeholder="email@example.com" />
                    <InputError :message="form.errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="password">{{ t('auth_ui.password_label') }}</Label>
                    <Input
                        id="password"
                        type="password"
                        required
                        tabindex="4"
                        autocomplete="new-password"
                        v-model="form.password"
                        :placeholder="t('auth_ui.password_label')"
                    />
                    <InputError :message="form.errors.password" />
                </div>

                <div class="grid gap-2">
                    <Label for="password_confirmation">{{ t('auth_ui.confirm_password') }}</Label>
                    <Input
                        id="password_confirmation"
                        type="password"
                        required
                        tabindex="5"
                        autocomplete="new-password"
                        v-model="form.password_confirmation"
                        :placeholder="t('auth_ui.confirm_password')"
                    />
                    <InputError :message="form.errors.password_confirmation" />
                </div>

                <Button type="submit" class="mt-2 w-full" tabindex="6" :disabled="form.processing">
                    <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                    {{ t('auth_ui.create_account') }}
                </Button>
            </div>

            <div class="text-center text-sm text-muted-foreground">
                {{ t('auth_ui.have_account') }}
                <TextLink :href="route('login')" class="underline underline-offset-4" tabindex="7">{{ t('common.log_in') }}</TextLink>
            </div>
        </form>
    </AuthBase>
</template>
