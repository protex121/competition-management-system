<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { ClipboardList } from 'lucide-vue-next';

interface RegistrationItem {
    id: number;
    status: string;
    created_at: string;
    withdrawn_at: string | null;
    category: {
        id: number;
        name: string;
        competition: { id: number; name: string };
    };
    user: { id: number; name: string } | null;
    team: { id: number; name: string } | null;
    can: { withdraw: boolean };
}

interface Props {
    registrations: RegistrationItem[];
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'My Registrations', href: route('registrations.index') }];

const formatStatus = (status: string): string => status.charAt(0).toUpperCase() + status.slice(1);

const statusClass = (status: string): string => {
    switch (status) {
        case 'confirmed':
            return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300';
        default:
            return 'bg-neutral-100 text-neutral-800 dark:bg-neutral-800/60 dark:text-neutral-300';
    }
};

const formatDate = (value: string | null): string => {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' });
};

const withdraw = (registrationId: number) => {
    if (!confirm('Withdraw this registration? This frees your slot for someone else.')) {
        return;
    }

    router.patch(route('registrations.withdraw', registrationId), {}, { preserveScroll: true });
};
</script>

<template>
    <Head title="My Registrations" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">
            <Heading title="My Registrations" description="Categories you or your teams are registered for" />

            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <ClipboardList class="h-5 w-5" />
                        Registrations
                    </CardTitle>
                </CardHeader>
                <CardContent class="p-0">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b text-left text-muted-foreground">
                                    <th class="px-6 py-3 font-medium">Competition</th>
                                    <th class="px-6 py-3 font-medium">Category</th>
                                    <th class="px-6 py-3 font-medium">As</th>
                                    <th class="px-6 py-3 font-medium">Status</th>
                                    <th class="px-6 py-3 font-medium">Registered</th>
                                    <th class="px-6 py-3 text-right font-medium">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="registration in registrations" :key="registration.id" class="border-b last:border-0">
                                    <td class="px-6 py-4 font-medium">{{ registration.category.competition.name }}</td>
                                    <td class="px-6 py-4 text-muted-foreground">{{ registration.category.name }}</td>
                                    <td class="px-6 py-4 text-muted-foreground">
                                        {{ registration.team ? registration.team.name : 'Individual' }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <span
                                            class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium"
                                            :class="statusClass(registration.status)"
                                        >
                                            {{ formatStatus(registration.status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-muted-foreground">{{ formatDate(registration.created_at) }}</td>
                                    <td class="px-6 py-4 text-right">
                                        <Button v-if="registration.can.withdraw" variant="outline" size="sm" @click="withdraw(registration.id)">
                                            Withdraw
                                        </Button>
                                    </td>
                                </tr>
                                <tr v-if="registrations.length === 0">
                                    <td colspan="6" class="px-6 py-8 text-center text-muted-foreground">
                                        No registrations yet. Browse competitions to register.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
