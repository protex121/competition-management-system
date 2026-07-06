<script setup lang="ts">
import { Separator } from '@/components/ui/separator';
import { cn } from '@/lib/utils';

interface Props {
    title: string;
    description?: string;
    showSeparator?: boolean;
}

withDefaults(defineProps<Props>(), {
    showSeparator: true,
});
</script>

<template>
    <div :class="cn('space-y-0.5', showSeparator && 'mb-8')">
        <h2 class="text-xl font-semibold tracking-tight">{{ title }}</h2>
        <p v-if="description" class="text-sm text-muted-foreground">
            {{ description }}
        </p>
    </div>
    <Separator v-if="showSeparator" class="my-6" />
</template>
