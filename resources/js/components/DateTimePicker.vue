<script setup lang="ts">
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        modelValue?: string;
        disabled?: boolean;
        id?: string;
        class?: string;
    }>(),
    {
        modelValue: '',
        disabled: false,
    },
);

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const datePart = computed({
    get: (): string => (props.modelValue ? props.modelValue.slice(0, 10) : ''),
    set: (date: string): void => {
        if (!date) {
            emit('update:modelValue', '');

            return;
        }

        const time = props.modelValue?.slice(11, 16) || '00:00';
        emit('update:modelValue', `${date}T${time}`);
    },
});

const timePart = computed({
    get: (): string => (props.modelValue ? props.modelValue.slice(11, 16) : ''),
    set: (time: string): void => {
        const date = datePart.value;

        if (!date) {
            return;
        }

        emit('update:modelValue', `${date}T${time || '00:00'}`);
    },
});
</script>

<template>
    <div :class="cn('flex gap-2', props.class)">
        <Input
            :id="id"
            v-model="datePart"
            type="date"
            :disabled="disabled"
            class="min-w-0 flex-1 [color-scheme:light] dark:[color-scheme:dark]"
        />
        <Input
            v-model="timePart"
            type="time"
            :disabled="disabled || !datePart"
            class="w-[8.5rem] shrink-0 [color-scheme:light] dark:[color-scheme:dark]"
        />
    </div>
</template>
