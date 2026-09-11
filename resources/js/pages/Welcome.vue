<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Head, Link } from '@inertiajs/vue3';
import { ClipboardList, Gavel, Trophy, UploadCloud } from 'lucide-vue-next';

interface Stage {
    icon: typeof ClipboardList;
    title: string;
    description: string;
}

const stages: Stage[] = [
    {
        icon: ClipboardList,
        title: 'Register',
        description: 'Participants join solo or as an approved team, within a category’s deadline and capacity.',
    },
    {
        icon: UploadCloud,
        title: 'Submit',
        description: 'Teams draft a title, description, link, and file — then finalize before judging opens.',
    },
    {
        icon: Gavel,
        title: 'Judge',
        description: 'Assigned judges score every entry against a shared rubric — and never their own work.',
    },
    {
        icon: Trophy,
        title: 'Rank',
        description: 'Scores aggregate into a public leaderboard the moment a competition closes.',
    },
];

interface Audience {
    title: string;
    description: string;
}

const audiences: Audience[] = [
    {
        title: 'Organizers',
        description: 'Create events, manage categories, review teams, and oversee every registration and submission from one dashboard.',
    },
    {
        title: 'Participants',
        description: 'Build a profile, form or join a team, register for a track, and submit your work before the deadline.',
    },
    {
        title: 'Judges',
        description: 'Score exactly the submissions you’re assigned to, fairly — the platform blocks you from scoring your own.',
    },
];
</script>

<template>
    <Head title="Welcome" />

    <div class="flex min-h-screen flex-col bg-background text-foreground">
        <header class="border-b">
            <div class="mx-auto flex max-w-6xl items-center justify-between gap-3 px-6 py-4">
                <div class="flex min-w-0 items-center gap-2.5">
                    <div class="flex size-8 shrink-0 items-center justify-center rounded-md bg-primary text-primary-foreground">
                        <AppLogoIcon class="size-5 fill-current" />
                    </div>
                    <span class="truncate text-sm font-semibold tracking-tight">{{ $page.props.name }}</span>
                </div>
                <nav class="flex shrink-0 items-center gap-3 sm:gap-4">
                    <Link v-if="$page.props.auth.user" :href="route('dashboard')">
                        <Button size="sm">Dashboard</Button>
                    </Link>
                    <template v-else>
                        <Link
                            :href="route('login')"
                            class="whitespace-nowrap text-sm text-muted-foreground transition-colors hover:text-foreground"
                            >Log in</Link
                        >
                        <Link :href="route('register')">
                            <Button size="sm">Get started</Button>
                        </Link>
                    </template>
                </nav>
            </div>
        </header>

        <main class="flex-1">
            <section class="mx-auto max-w-6xl px-6 py-20 sm:py-28">
                <div class="mx-auto max-w-3xl text-center">
                    <p class="mb-4 text-xs font-semibold uppercase tracking-widest text-muted-foreground">
                        Multi-tenant competition platform
                    </p>
                    <h1 class="text-4xl font-bold tracking-tight text-balance sm:text-5xl">
                        Run your hackathon from registration to results.
                    </h1>
                    <p class="mt-5 text-lg text-muted-foreground text-balance">
                        One workspace for organizers, participants, and judges &mdash; teams register, submit their work, get
                        scored against a shared rubric, and see a live leaderboard the moment your event closes.
                    </p>
                    <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                        <Link :href="route('register')">
                            <Button size="lg">Create your organization</Button>
                        </Link>
                        <Link :href="route('login')">
                            <Button size="lg" variant="outline">Log in</Button>
                        </Link>
                    </div>
                </div>
            </section>

            <section class="border-y bg-muted/40">
                <div class="mx-auto max-w-6xl px-6 py-16">
                    <div class="grid gap-px overflow-hidden rounded-lg border bg-border sm:grid-cols-2 lg:grid-cols-4">
                        <div v-for="(stage, i) in stages" :key="stage.title" class="bg-background p-6">
                            <span class="font-mono text-xs text-muted-foreground">0{{ i + 1 }}</span>
                            <component :is="stage.icon" class="mt-3 size-5" />
                            <h3 class="mt-3 font-semibold">{{ stage.title }}</h3>
                            <p class="mt-1.5 text-sm text-muted-foreground">{{ stage.description }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="mx-auto max-w-6xl px-6 py-16 sm:py-20">
                <h2 class="text-center text-2xl font-bold tracking-tight">Built for every role in your event</h2>
                <div class="mt-10 grid gap-6 sm:grid-cols-3">
                    <Card v-for="audience in audiences" :key="audience.title">
                        <CardContent class="p-6">
                            <h3 class="font-semibold">{{ audience.title }}</h3>
                            <p class="mt-2 text-sm text-muted-foreground">{{ audience.description }}</p>
                        </CardContent>
                    </Card>
                </div>
            </section>
        </main>

        <footer class="border-t">
            <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-3 px-6 py-8 text-sm text-muted-foreground sm:flex-row">
                <span>{{ $page.props.name }}</span>
                <div class="flex items-center gap-4">
                    <Link :href="route('login')" class="transition-colors hover:text-foreground">Log in</Link>
                    <Link :href="route('register')" class="transition-colors hover:text-foreground">Register</Link>
                </div>
            </div>
        </footer>
    </div>
</template>
