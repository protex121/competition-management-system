<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

interface CompetitionContext {
    id: number;
    name: string;
    min_team_size: number | null;
    max_team_size: number | null;
}

interface Props {
    competition: CompetitionContext;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Teams', href: route('competitions.teams.index', props.competition.id) },
    { title: 'Create', href: route('competitions.teams.create', props.competition.id) },
];

const form = useForm({
    name: '',
});

const submit = () => {
    form.post(route('competitions.teams.store', props.competition.id));
};
</script>

<template>
    <Head :title="`Create team — ${competition.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">
            <Heading
                :title="`Create team for ${competition.name}`"
                :description="`You will be the team captain. Team size: ${competition.min_team_size ?? '?'}–${competition.max_team_size ?? '?'} members.`"
            />

            <Card class="max-w-2xl">
                <CardHeader>
                    <CardTitle>Team details</CardTitle>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="grid gap-2">
                            <Label for="name">Team name</Label>
                            <Input id="name" v-model="form.name" required placeholder="Alpha Squad" />
                            <InputError :message="form.errors.name" />
                        </div>

                        <div class="flex items-center gap-4">
                            <Button type="submit" :disabled="form.processing">
                                <LoaderCircle v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                                Create team
                            </Button>
                            <Button as-child variant="outline">
                                <Link :href="route('competitions.teams.index', competition.id)">Cancel</Link>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
