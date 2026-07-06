<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { computed } from 'vue';

const registrationMode = defineModel<string>('registrationMode', { required: true });
const minTeamSize = defineModel<string | number>('minTeamSize', { required: true });
const maxTeamSize = defineModel<string | number>('maxTeamSize', { required: true });
const requiresCoach = defineModel<boolean>('requiresCoach', { required: true });

defineProps<{
    errors: Record<string, string | undefined>;
    disabled?: boolean;
}>();

const registrationModes = [
    { value: 'individual', label: 'Individual only' },
    { value: 'team', label: 'Teams only' },
    { value: 'both', label: 'Individual & teams' },
];

const showTeamSizes = computed(
    () => registrationMode.value === 'team' || registrationMode.value === 'both',
);
</script>

<template>
    <div class="space-y-6">
        <div class="grid gap-2">
            <Label for="registration_mode">Participation mode</Label>
            <select
                id="registration_mode"
                v-model="registrationMode"
                :disabled="disabled"
                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
            >
                <option v-for="mode in registrationModes" :key="mode.value" :value="mode.value">
                    {{ mode.label }}
                </option>
            </select>
            <InputError :message="errors.registration_mode" />
        </div>

        <div v-if="showTeamSizes" class="grid gap-4 sm:grid-cols-2">
            <div class="grid gap-2">
                <Label for="min_team_size">Min team size</Label>
                <Input
                    id="min_team_size"
                    v-model="minTeamSize"
                    type="number"
                    min="1"
                    :disabled="disabled"
                    placeholder="2"
                />
                <InputError :message="errors.min_team_size" />
            </div>
            <div class="grid gap-2">
                <Label for="max_team_size">Max team size</Label>
                <Input
                    id="max_team_size"
                    v-model="maxTeamSize"
                    type="number"
                    min="1"
                    :disabled="disabled"
                    placeholder="5"
                />
                <InputError :message="errors.max_team_size" />
            </div>
        </div>

        <div v-if="showTeamSizes" class="flex items-center gap-2">
            <input
                id="requires_coach"
                v-model="requiresCoach"
                type="checkbox"
                :disabled="disabled"
                class="h-4 w-4 rounded border-input"
            />
            <Label for="requires_coach" class="font-normal">Require a coach on each team</Label>
            <InputError :message="errors.requires_coach" />
        </div>
    </div>
</template>
