<script setup lang="ts">
import { TransitionRoot } from '@headlessui/vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type TeamDetail, type TeamPermissions } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { LoaderCircle, Trash2 } from 'lucide-vue-next';

interface Props {
    team: TeamDetail;
    can: TeamPermissions;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: props.team.competition.name, href: route('competitions.teams.index', props.team.competition.id) },
    { title: props.team.name, href: route('teams.show', props.team.id) },
];

const form = useForm({
    name: props.team.name,
});

const inviteForm = useForm({
    email: '',
});

const submit = () => {
    form.put(route('teams.update', props.team.id), {
        preserveScroll: true,
    });
};

const destroy = () => {
    if (!confirm('Delete this team? This cannot be undone.')) {
        return;
    }

    router.delete(route('teams.destroy', props.team.id));
};

const sendInvite = () => {
    inviteForm.post(route('teams.invitations.store', props.team.id), {
        preserveScroll: true,
        onSuccess: () => inviteForm.reset(),
    });
};

const revokeInvite = (invitationId: number) => {
    router.delete(route('teams.invitations.destroy', [props.team.id, invitationId]), {
        preserveScroll: true,
    });
};

const submitForApproval = () => {
    router.post(route('teams.submit', props.team.id));
};

const transferCaptain = (memberId: number) => {
    if (!confirm('Transfer captaincy to this member?')) {
        return;
    }

    router.post(route('teams.members.transfer-captain', [props.team.id, memberId]), {}, { preserveScroll: true });
};

const removeMember = (memberId: number) => {
    if (!confirm('Remove this member from the team?')) {
        return;
    }

    router.delete(route('teams.members.destroy', [props.team.id, memberId]), { preserveScroll: true });
};

const leaveTeam = () => {
    if (!confirm('Leave this team?')) {
        return;
    }

    router.post(route('teams.leave', props.team.id));
};

const formatStatus = (status: string): string =>
    status
        .split('_')
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');

const statusClass = (status: string): string => {
    switch (status) {
        case 'approved':
            return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300';
        case 'pending_approval':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300';
        case 'rejected':
            return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300';
        default:
            return 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300';
    }
};

const formatRole = (role: string): string => (role === 'captain' ? 'Captain' : 'Member');
</script>

<template>
    <Head :title="team.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">
            <div class="flex items-start justify-between gap-4">
                <Heading :title="team.name" :description="`Competing in ${team.competition.name}`" :show-separator="false" />
                <span class="inline-flex shrink-0 rounded-full px-2.5 py-0.5 text-xs font-medium" :class="statusClass(team.status)">
                    {{ formatStatus(team.status) }}
                </span>
            </div>

            <div v-if="team.status === 'rejected' && team.rejection_reason" class="rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-900 dark:bg-red-950/30 dark:text-red-300">
                <p class="font-medium">Rejection reason</p>
                <p class="mt-1">{{ team.rejection_reason }}</p>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <Card v-if="can.update">
                    <CardHeader>
                        <CardTitle>Team name</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form @submit.prevent="submit" class="space-y-4">
                            <div class="grid gap-2">
                                <Label for="name">Name</Label>
                                <Input id="name" v-model="form.name" required />
                                <InputError :message="form.errors.name" />
                            </div>
                            <div class="flex items-center gap-4">
                                <Button type="submit" size="sm" :disabled="form.processing">
                                    <LoaderCircle v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                                    Save
                                </Button>
                                <TransitionRoot
                                    :show="form.recentlySuccessful"
                                    enter="transition ease-in-out"
                                    enter-from="opacity-0"
                                    leave="transition ease-in-out"
                                    leave-to="opacity-0"
                                >
                                    <p class="text-sm text-muted-foreground">Saved.</p>
                                </TransitionRoot>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Roster ({{ team.members.length }})</CardTitle>
                    </CardHeader>
                    <CardContent class="p-0">
                        <ul class="divide-y">
                            <li v-for="member in team.members" :key="member.id" class="flex items-center justify-between gap-3 px-6 py-3 text-sm">
                                <div>
                                    <p class="font-medium">{{ member.user.name }}</p>
                                    <p class="text-muted-foreground">{{ member.user.email }}</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-muted-foreground">{{ formatRole(member.role) }}</span>
                                    <Button
                                        v-if="member.can?.transferCaptain"
                                        variant="outline"
                                        size="sm"
                                        @click="transferCaptain(member.id)"
                                    >
                                        Make captain
                                    </Button>
                                    <Button
                                        v-if="member.can?.remove"
                                        variant="ghost"
                                        size="sm"
                                        @click="removeMember(member.id)"
                                    >
                                        Remove
                                    </Button>
                                </div>
                            </li>
                            <li v-if="team.members.length === 0" class="px-6 py-4 text-sm text-muted-foreground">No members yet.</li>
                        </ul>
                        <p v-if="team.pending_invitations.length > 0" class="border-t px-6 py-3 text-xs text-muted-foreground">
                            {{ team.pending_invitations.length }} pending invitation(s)
                        </p>
                    </CardContent>
                </Card>

                <Card v-if="can.invite">
                    <CardHeader>
                        <CardTitle>Invite member</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <form @submit.prevent="sendInvite" class="flex gap-2">
                            <div class="grid flex-1 gap-2">
                                <Label for="email" class="sr-only">Email</Label>
                                <Input id="email" v-model="inviteForm.email" type="email" placeholder="colleague@example.com" required />
                                <InputError :message="inviteForm.errors.email" />
                            </div>
                            <Button type="submit" :disabled="inviteForm.processing">Send</Button>
                        </form>
                        <ul v-if="team.pending_invitations.length > 0" class="divide-y rounded-md border">
                            <li
                                v-for="invitation in team.pending_invitations"
                                :key="invitation.id"
                                class="flex items-center justify-between px-4 py-2 text-sm"
                            >
                                <span>{{ invitation.email }}</span>
                                <Button
                                    v-if="invitation.can?.revoke"
                                    variant="ghost"
                                    size="sm"
                                    @click="revokeInvite(invitation.id)"
                                >
                                    Revoke
                                </Button>
                            </li>
                        </ul>
                    </CardContent>
                </Card>
            </div>

            <div class="flex items-center gap-4">
                <Button as-child variant="outline">
                    <Link :href="route('competitions.teams.index', team.competition.id)">Back to teams</Link>
                </Button>
                <Button v-if="can.submit" @click="submitForApproval">Submit for approval</Button>
                <Button v-if="can.leave" variant="outline" @click="leaveTeam">Leave team</Button>
                <Button v-if="can.delete" variant="destructive" size="sm" @click="destroy">
                    <Trash2 class="mr-2 h-4 w-4" />
                    Delete team
                </Button>
            </div>
        </div>
    </AppLayout>
</template>
