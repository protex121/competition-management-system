<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';

interface InvitationItem {
    id: number;
    email: string;
    expires_at: string;
    team: { id: number; name: string };
    competition: { id: number; name: string };
    invited_by: { name: string } | null;
}

interface Props {
    invitations: {
        data: InvitationItem[];
    };
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Invitations', href: route('invitations.index') },
];

const accept = (id: number) => {
    router.post(route('invitations.accept', id));
};

const decline = (id: number) => {
    router.post(route('invitations.decline', id));
};
</script>

<template>
    <Head title="Team invitations" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">
            <Heading title="Team invitations" description="Pending invitations to join teams" />

            <Card>
                <CardHeader>
                    <CardTitle>Inbox</CardTitle>
                </CardHeader>
                <CardContent class="p-0">
                    <ul class="divide-y">
                        <li v-for="invitation in invitations.data" :key="invitation.id" class="px-6 py-4">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="font-medium">{{ invitation.team.name }}</p>
                                    <p class="text-sm text-muted-foreground">
                                        {{ invitation.competition.name }}
                                        <span v-if="invitation.invited_by"> · invited by {{ invitation.invited_by.name }}</span>
                                    </p>
                                </div>
                                <div class="flex gap-2">
                                    <Button size="sm" @click="accept(invitation.id)">Accept</Button>
                                    <Button size="sm" variant="outline" @click="decline(invitation.id)">Decline</Button>
                                </div>
                            </div>
                        </li>
                        <li v-if="invitations.data.length === 0" class="px-6 py-8 text-center text-sm text-muted-foreground">
                            No pending invitations.
                        </li>
                    </ul>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
