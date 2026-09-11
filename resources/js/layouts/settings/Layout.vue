<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useTranslation } from '@/composables/useTranslation';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const { t } = useTranslation();

const sidebarNavItems = computed<(NavItem & { routeName: string })[]>(() => [
    {
        title: t('settings_ui.nav_profile'),
        href: route('profile.edit'),
        routeName: 'profile.edit',
    },
    {
        title: t('settings_ui.nav_password'),
        href: route('password.edit'),
        routeName: 'password.edit',
    },
    {
        title: t('settings_ui.nav_appearance'),
        href: route('appearance'),
        routeName: 'appearance',
    },
]);
</script>

<template>
    <div class="px-4 py-6">
        <Heading :title="t('settings_ui.title')" :description="t('settings_ui.description')" />

        <div class="flex flex-col space-y-8 md:space-y-0 lg:flex-row lg:space-x-12 lg:space-y-0">
            <aside class="w-full max-w-xl lg:w-48">
                <nav class="flex flex-col space-x-0 space-y-1">
                    <Button
                        v-for="item in sidebarNavItems"
                        :key="item.href"
                        variant="ghost"
                        :class="['w-full justify-start', { 'bg-muted': route().current(item.routeName) }]"
                        as-child
                    >
                        <Link :href="item.href">
                            {{ item.title }}
                        </Link>
                    </Button>
                </nav>
            </aside>

            <Separator class="my-6 md:hidden" />

            <div class="flex-1 md:max-w-2xl">
                <section class="max-w-xl space-y-12">
                    <slot />
                </section>
            </div>
        </div>
    </div>
</template>
