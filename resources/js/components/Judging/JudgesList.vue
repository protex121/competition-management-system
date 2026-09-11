<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { type CompetitionJudgeAssignment } from '@/types';
import { router, useForm } from '@inertiajs/vue3';

interface JudgeOption {
    id: number;
    name: string;
    email: string;
}

interface Props {
    competitionId: number;
    judges: CompetitionJudgeAssignment[];
    availableJudges: JudgeOption[];
    canManage: boolean;
}

const props = defineProps<Props>();

const assignForm = useForm({
    user_id: '' as string | number,
});

const assignedUserIds = new Set(props.judges.map((assignment) => assignment.user.id));

const assign = () => {
    assignForm.post(route('competitions.judges.store', props.competitionId), {
        preserveScroll: true,
        onSuccess: () => assignForm.reset(),
    });
};

const revoke = (assignmentId: number) => {
    if (!confirm('Revoke this judge from the competition?')) {
        return;
    }

    router.delete(route('competitions.judges.destroy', [props.competitionId, assignmentId]), { preserveScroll: true });
};
</script>

<template>
    <div class="space-y-4">
        <ul v-if="judges.length > 0" class="divide-y rounded-md border">
            <li v-for="assignment in judges" :key="assignment.id" class="flex items-center justify-between px-4 py-2 text-sm">
                <div>
                    <p class="font-medium">{{ assignment.user.name }}</p>
                    <p class="text-muted-foreground">{{ assignment.user.email }}</p>
                </div>
                <Button v-if="canManage" variant="ghost" size="sm" @click="revoke(assignment.id)">Revoke</Button>
            </li>
        </ul>
        <p v-else class="text-sm text-muted-foreground">No judges assigned yet.</p>

        <form v-if="canManage" class="flex gap-2" @submit.prevent="assign">
            <div class="grid flex-1 gap-2">
                <Label for="judge" class="sr-only">Judge</Label>
                <select
                    id="judge"
                    v-model="assignForm.user_id"
                    required
                    class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                >
                    <option value="" disabled>Select a judge</option>
                    <option v-for="judge in availableJudges" :key="judge.id" :value="judge.id" :disabled="assignedUserIds.has(judge.id)">
                        {{ judge.name }} ({{ judge.email }}){{ assignedUserIds.has(judge.id) ? ' — assigned' : '' }}
                    </option>
                </select>
            </div>
            <Button type="submit" :disabled="assignForm.processing || availableJudges.length === 0">Assign</Button>
        </form>

        <p v-if="canManage && availableJudges.length === 0" class="text-sm text-muted-foreground">
            No judge-role users available in your organization.
        </p>
    </div>
</template>
