<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import type { SharedData } from '@/types';
import { router, usePage } from '@inertiajs/vue3';
import { Languages } from 'lucide-vue-next';

const page = usePage<SharedData>();

const switchTo = (locale: string) => {
    const current = route().current();

    if (!current) {
        return;
    }

    router.visit(route(current, { ...route().params, locale }));
};
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button variant="ghost" size="sm" class="gap-1.5">
                <Languages class="size-4" />
                {{ page.props.availableLocales[page.props.locale] }}
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end">
            <DropdownMenuItem
                v-for="(label, code) in page.props.availableLocales"
                :key="code"
                :disabled="code === page.props.locale"
                @click="switchTo(code)"
            >
                {{ label }}
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
