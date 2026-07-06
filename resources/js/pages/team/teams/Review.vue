<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

interface ReviewTeam {
    id: number;
    name: string;
    submitted_at: string | null;
    captain: { name: string } | null;
    members: { id: number; user: { name: string } }[];
    can: {
        approve: boolean;
        reject: boolean;
    };
}

interface Props {
    competition: { id: number; name: string };
    teams: { data: ReviewTeam[] };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Competitions', href: route('competitions.index') },
    { title: props.competition.name, href: route('competitions.edit', props.competition.id) },
    { title: 'Team review', href: route('competitions.teams.review', props.competition.id) },
];

const rejectingTeamId = ref<number | null>(null);

const rejectForm = useForm({
    rejection_reason: '',
});

const approve = (teamId: number) => {
    router.post(route('teams.approve', teamId));
};

const startReject = (teamId: number) => {
    rejectingTeamId.value = teamId;
    rejectForm.reset();
};

const submitReject = (teamId: number) => {
    rejectForm.post(route('teams.reject', teamId), {
        onSuccess: () => {
            rejectingTeamId.value = null;
        },
    });
};

const formatDate = (value: string | null): string => {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleString(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
};
</script>

<template>
    <Head :title="`Review teams — ${competition.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">
            <Heading
                :title="`Pending teams — ${competition.name}`"
                description="Review and approve or reject team registrations"
            />

            <Card>
                <CardHeader>
                    <CardTitle>Pending approval</CardTitle>
                </CardHeader>
                <CardContent class="p-0">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b text-left text-muted-foreground">
                                    <th class="px-6 py-3 font-medium">Team</th>
                                    <th class="px-6 py-3 font-medium">Captain</th>
                                    <th class="px-6 py-3 font-medium">Members</th>
                                    <th class="px-6 py-3 font-medium">Submitted</th>
                                    <th class="px-6 py-3 font-medium text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="team in teams.data" :key="team.id" class="border-b last:border-0 align-top">
                                    <td class="px-6 py-4 font-medium">
                                        <Link :href="route('teams.show', team.id)" class="hover:underline">{{ team.name }}</Link>
                                    </td>
                                    <td class="px-6 py-4 text-muted-foreground">{{ team.captain?.name ?? '—' }}</td>
                                    <td class="px-6 py-4 text-muted-foreground">{{ team.members.length }}</td>
                                    <td class="px-6 py-4 text-muted-foreground">{{ formatDate(team.submitted_at) }}</td>
                                    <td class="px-6 py-4 text-right">
                                        <div v-if="rejectingTeamId !== team.id" class="flex justify-end gap-2">
                                            <Button
                                                v-if="team.can.approve"
                                                size="sm"
                                                @click="approve(team.id)"
                                            >
                                                Approve
                                            </Button>
                                            <Button
                                                v-if="team.can.reject"
                                                size="sm"
                                                variant="outline"
                                                @click="startReject(team.id)"
                                            >
                                                Reject
                                            </Button>
                                        </div>
                                        <form v-else class="space-y-2 text-left" @submit.prevent="submitReject(team.id)">
                                            <div class="grid gap-1">
                                                <Label :for="`reason-${team.id}`">Reason (optional)</Label>
                                                <Input
                                                    :id="`reason-${team.id}`"
                                                    v-model="rejectForm.rejection_reason"
                                                    placeholder="Why was this team rejected?"
                                                />
                                                <InputError :message="rejectForm.errors.rejection_reason" />
                                            </div>
                                            <div class="flex justify-end gap-2">
                                                <Button type="button" size="sm" variant="ghost" @click="rejectingTeamId = null">
                                                    Cancel
                                                </Button>
                                                <Button type="submit" size="sm" variant="destructive" :disabled="rejectForm.processing">
                                                    Confirm reject
                                                </Button>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                                <tr v-if="teams.data.length === 0">
                                    <td colspan="5" class="px-6 py-8 text-center text-muted-foreground">
                                        No teams awaiting review.
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
