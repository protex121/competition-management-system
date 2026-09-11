<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

import AppearanceTabs from '@/components/AppearanceTabs.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import { useTranslation } from '@/composables/useTranslation';
import { type BreadcrumbItem } from '@/types';

import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';

const { t } = useTranslation();

const breadcrumbItems = computed<BreadcrumbItem[]>(() => [
    {
        title: t('settings_ui.appearance_title'),
        href: route('appearance'),
    },
]);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="t('settings_ui.appearance_title')" />

        <SettingsLayout>
            <div class="space-y-6">
                <HeadingSmall :title="t('settings_ui.appearance_title')" :description="t('settings_ui.appearance_description')" />
                <AppearanceTabs />
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
