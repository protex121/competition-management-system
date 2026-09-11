<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type Organization, type PaginatedUsers, type User } from '@/types';
import { useTranslation } from '@/composables/useTranslation';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    users: PaginatedUsers<User>;
}

defineProps<Props>();

const { t } = useTranslation();
const page = usePage();
const isSuperAdmin = computed(() => page.props.auth.user?.role === 'super-admin');

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: t('nav.users'), href: route('users.index') },
]);

const formatRole = (role: string): string =>
    role
        .split('-')
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');

const organizationName = (organization: Organization | null | undefined): string => organization?.name ?? '—';
</script>

<template>
    <Head :title="t('nav.users')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">
            <div class="mb-8">
                <div class="flex items-start justify-between gap-4">
                    <Heading :title="t('nav.users')" :description="t('identity.index_description')" :show-separator="false" />
                    <Button as-child class="shrink-0">
                        <Link :href="route('users.create')">
                            <Plus class="mr-2 h-4 w-4" />
                            {{ t('identity.add_user') }}
                        </Link>
                    </Button>
                </div>
                <Separator class="mt-6" />
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>{{ t('identity.all_users') }}</CardTitle>
                </CardHeader>
                <CardContent class="p-0">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b text-left text-muted-foreground">
                                    <th class="px-6 py-3 font-medium">{{ t('identity.th_name') }}</th>
                                    <th class="px-6 py-3 font-medium">{{ t('identity.th_email') }}</th>
                                    <th class="px-6 py-3 font-medium">{{ t('identity.th_role') }}</th>
                                    <th v-if="isSuperAdmin" class="px-6 py-3 font-medium">{{ t('identity.th_organization') }}</th>
                                    <th class="px-6 py-3 font-medium">{{ t('identity.th_status') }}</th>
                                    <th class="px-6 py-3 font-medium text-right">{{ t('identity.th_actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="user in users.data" :key="user.id" class="border-b last:border-0">
                                    <td class="px-6 py-4 font-medium">{{ user.name }}</td>
                                    <td class="px-6 py-4 text-muted-foreground">{{ user.email }}</td>
                                    <td class="px-6 py-4">{{ formatRole(user.role) }}</td>
                                    <td v-if="isSuperAdmin" class="px-6 py-4 text-muted-foreground">
                                        {{ organizationName(user.organization) }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <span
                                            class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium"
                                            :class="
                                                user.deactivated_at
                                                    ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300'
                                                    : 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300'
                                            "
                                        >
                                            {{ user.deactivated_at ? t('identity.status_deactivated') : t('identity.status_active') }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <Button as-child variant="outline" size="sm">
                                            <Link :href="route('users.edit', user.id)">{{ t('common.edit') }}</Link>
                                        </Button>
                                    </td>
                                </tr>
                                <tr v-if="users.data.length === 0">
                                    <td :colspan="isSuperAdmin ? 6 : 5" class="px-6 py-8 text-center text-muted-foreground">
                                        {{ t('identity.empty_users') }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-if="users.links.length > 3" class="flex items-center justify-center gap-1 border-t px-6 py-4">
                        <template v-for="(link, index) in users.links" :key="index">
                            <Button
                                v-if="link.url"
                                as-child
                                size="sm"
                                :variant="link.active ? 'default' : 'outline'"
                            >
                                <Link :href="link.url" preserve-scroll>
                                    <span v-html="link.label" />
                                </Link>
                            </Button>
                            <span v-else class="px-2 text-muted-foreground" v-html="link.label" />
                        </template>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
