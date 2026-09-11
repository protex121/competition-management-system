<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Head, Link } from '@inertiajs/vue3';
import { Clock, Eye, Gavel, ListChecks, Lock, ShieldCheck, SlidersHorizontal, Trophy, UploadCloud, UserCog, UserRoundCheck, Users2 } from 'lucide-vue-next';

interface Stage {
    icon: typeof Trophy;
    title: string;
    description: string;
}

const stages: Stage[] = [
    {
        icon: Users2,
        title: 'Register',
        description: 'Participants join solo or as an approved team, within a category’s deadline and capacity — the slot is gone the moment it’s confirmed.',
    },
    {
        icon: UploadCloud,
        title: 'Submit',
        description: 'Teams draft a title, description, link, and one file, editing freely right up until they finalize — then it locks for good.',
    },
    {
        icon: Gavel,
        title: 'Judge',
        description: 'Assigned judges score every finalized entry against the competition’s own rubric, and can never score their own work.',
    },
    {
        icon: Trophy,
        title: 'Rank',
        description: 'The moment a competition closes, scores aggregate into a ranked leaderboard and go live on a public results page.',
    },
];

interface Audience {
    icon: typeof Trophy;
    title: string;
    description: string;
}

const audiences: Audience[] = [
    {
        icon: UserCog,
        title: 'Organizers',
        description: 'Create events, manage categories, review teams, assign judges, and oversee every registration and submission from one dashboard.',
    },
    {
        icon: UserRoundCheck,
        title: 'Participants',
        description: 'Build a profile, form or join a team, register for a track, and submit your work before the deadline — solo or together.',
    },
    {
        icon: ShieldCheck,
        title: 'Judges',
        description: 'Score exactly the submissions you’re assigned to, fairly — the platform blocks you from ever scoring your own entry.',
    },
];

interface Point {
    icon: typeof Trophy;
    text: string;
}

const judgingPoints: Point[] = [
    { icon: ListChecks, text: 'Every score is entered against the competition’s own rubric — one scorecard, every criterion, in one action.' },
    { icon: ShieldCheck, text: 'A judge can never score their own registration or their own team’s submission, even if they’re also competing.' },
    { icon: Lock, text: 'Scores stay editable right up until the organizer closes the competition — then they’re frozen for good.' },
];

const organizerPoints: Point[] = [
    { icon: UserCog, text: 'Assign judges from your own organization’s members — revoke access just as easily.' },
    { icon: SlidersHorizontal, text: 'Build a rubric per competition: name each criterion and set its own maximum score.' },
    { icon: Clock, text: 'Registration and submission windows, plus per-category capacity, are enforced automatically — no manual policing.' },
    { icon: Eye, text: 'Read-only oversight of every registration and submission, with live score counts and working file downloads.' },
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
            <!-- Hero -->
            <section class="mx-auto max-w-6xl px-6 pt-20 sm:pt-28">
                <div class="mx-auto max-w-3xl text-center">
                    <p class="mb-4 text-xs font-semibold uppercase tracking-widest text-muted-foreground">
                        Multi-tenant competition platform
                    </p>
                    <h1 class="text-4xl font-bold tracking-tight text-balance sm:text-5xl">
                        Run your hackathon from registration to results.
                    </h1>
                    <p class="mt-5 text-lg text-muted-foreground text-balance">
                        One workspace for organizers, participants, and judges &mdash; teams register, submit their work, get
                        scored against a shared rubric, and see a live leaderboard the moment your event closes. Every
                        organization's data stays walled off from every other, on the same platform.
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

                <!-- Hero illustration -->
                <div class="mx-auto mt-16 max-w-3xl">
                    <div class="flex items-center justify-center rounded-xl border bg-muted/30 px-6 py-14 sm:py-20">
                        <svg viewBox="0 0 480 260" class="h-auto w-full max-w-md" fill="none" aria-hidden="true">
                            <line x1="30" y1="234" x2="450" y2="234" class="stroke-border" stroke-width="2" />
                            <rect x="88" y="130" width="84" height="104" rx="10" class="fill-muted" />
                            <circle cx="130" cy="110" r="19" class="fill-background stroke-border" stroke-width="2" />
                            <text x="130" y="117" text-anchor="middle" class="fill-foreground" font-size="16" font-weight="700">2</text>
                            <rect x="198" y="78" width="84" height="156" rx="10" class="fill-foreground" />
                            <circle cx="240" cy="58" r="22" class="fill-foreground stroke-border" stroke-width="2" />
                            <text x="240" y="65" text-anchor="middle" class="fill-background" font-size="18" font-weight="700">1</text>
                            <rect x="308" y="164" width="84" height="70" rx="10" class="fill-muted" />
                            <circle cx="350" cy="144" r="19" class="fill-background stroke-border" stroke-width="2" />
                            <text x="350" y="151" text-anchor="middle" class="fill-foreground" font-size="16" font-weight="700">3</text>
                        </svg>
                    </div>
                    <p class="mt-3 text-center text-sm text-muted-foreground">
                        A real leaderboard, computed the moment a competition closes &mdash; no export, no spreadsheet.
                    </p>
                </div>
            </section>

            <!-- Pipeline -->
            <section class="mt-20 border-y bg-muted/40 sm:mt-28">
                <div class="mx-auto max-w-6xl px-6 py-16">
                    <h2 class="text-center text-2xl font-bold tracking-tight">The full lifecycle, built in</h2>
                    <p class="mx-auto mt-2 max-w-2xl text-center text-muted-foreground">
                        From the moment a competition opens to the moment it closes, every stage is one connected flow.
                    </p>
                    <div class="mt-10 grid gap-px overflow-hidden rounded-lg border bg-border sm:grid-cols-2 lg:grid-cols-4">
                        <div v-for="(stage, i) in stages" :key="stage.title" class="bg-background p-6">
                            <span class="font-mono text-xs text-muted-foreground">0{{ i + 1 }}</span>
                            <component :is="stage.icon" class="mt-3 size-5" />
                            <h3 class="mt-3 font-semibold">{{ stage.title }}</h3>
                            <p class="mt-1.5 text-sm text-muted-foreground">{{ stage.description }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Judging fairness split -->
            <section class="mx-auto max-w-6xl px-6 py-16 sm:py-24">
                <div class="grid items-center gap-10 lg:grid-cols-2 lg:gap-16">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-widest text-muted-foreground">For judges</p>
                        <h2 class="mt-3 text-2xl font-bold tracking-tight text-balance sm:text-3xl">Judging that can't be gamed.</h2>
                        <p class="mt-3 text-muted-foreground">
                            Fairness is enforced by the platform, not by trust. A judge only ever sees what they're supposed to
                            &mdash; and never what they aren't.
                        </p>
                        <ul class="mt-6 space-y-4">
                            <li v-for="point in judgingPoints" :key="point.text" class="flex gap-3">
                                <component :is="point.icon" class="mt-0.5 size-5 shrink-0 text-muted-foreground" />
                                <span class="text-sm text-foreground/90">{{ point.text }}</span>
                            </li>
                        </ul>
                    </div>
                    <div class="flex items-center justify-center rounded-xl border bg-muted/30 px-6 py-12">
                        <svg viewBox="0 0 480 320" class="h-auto w-full max-w-sm" fill="none" aria-hidden="true">
                            <rect x="90" y="36" width="300" height="256" rx="16" class="fill-card stroke-border" stroke-width="2" />
                            <rect x="195" y="20" width="90" height="26" rx="8" class="fill-muted stroke-border" stroke-width="2" />
                            <g v-for="(row, idx) in [0, 1, 2]" :key="row">
                                <rect :y="88 + idx * 62" x="120" width="130" height="10" rx="5" class="fill-muted-foreground/35" />
                                <rect :y="108 + idx * 62" x="120" width="240" height="10" rx="5" class="fill-muted" />
                                <rect
                                    :y="108 + idx * 62"
                                    x="120"
                                    :width="idx === 0 ? 200 : idx === 1 ? 150 : 220"
                                    height="10"
                                    rx="5"
                                    class="fill-foreground"
                                />
                            </g>
                            <circle cx="342" cy="254" r="26" class="fill-foreground" />
                            <path d="M330 254l8 8 16-17" class="stroke-background" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </div>
                </div>
            </section>

            <!-- Organizer control split -->
            <section class="border-t bg-muted/40">
                <div class="mx-auto max-w-6xl px-6 py-16 sm:py-24">
                    <div class="grid items-center gap-10 lg:grid-cols-2 lg:gap-16">
                        <div class="flex items-center justify-center rounded-xl border bg-muted/30 px-6 py-12 lg:order-2">
                            <svg viewBox="0 0 480 320" class="h-auto w-full max-w-sm" fill="none" aria-hidden="true">
                                <rect x="60" y="30" width="360" height="260" rx="16" class="fill-card stroke-border" stroke-width="2" />
                                <g v-for="(row, idx) in [0, 1, 2]" :key="row">
                                    <circle :cy="88 + idx * 62" cx="104" r="16" class="fill-muted" />
                                    <rect :y="82 + idx * 62" x="136" width="140" height="12" rx="6" class="fill-muted-foreground/35" />
                                    <rect :y="78 + idx * 62" x="336" width="44" height="22" rx="11" :class="idx !== 1 ? 'fill-foreground' : 'fill-muted'" />
                                    <circle :cy="89 + idx * 62" :cx="idx !== 1 ? 369 : 347" r="8" class="fill-background" />
                                </g>
                            </svg>
                        </div>
                        <div class="lg:order-1">
                            <p class="text-xs font-semibold uppercase tracking-widest text-muted-foreground">For organizers</p>
                            <h2 class="mt-3 text-2xl font-bold tracking-tight text-balance sm:text-3xl">
                                Everything you need, nothing you have to police by hand.
                            </h2>
                            <p class="mt-3 text-muted-foreground">
                                Set the rules once &mdash; the platform enforces them for every registration, every
                                submission, every score.
                            </p>
                            <ul class="mt-6 space-y-4">
                                <li v-for="point in organizerPoints" :key="point.text" class="flex gap-3">
                                    <component :is="point.icon" class="mt-0.5 size-5 shrink-0 text-muted-foreground" />
                                    <span class="text-sm text-foreground/90">{{ point.text }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Roles summary -->
            <section class="mx-auto max-w-6xl px-6 py-16 sm:py-24">
                <h2 class="text-center text-2xl font-bold tracking-tight">Built for every role in your event</h2>
                <div class="mt-10 grid gap-6 sm:grid-cols-3">
                    <Card v-for="audience in audiences" :key="audience.title">
                        <CardContent class="p-6">
                            <component :is="audience.icon" class="size-5 text-muted-foreground" />
                            <h3 class="mt-3 font-semibold">{{ audience.title }}</h3>
                            <p class="mt-2 text-sm text-muted-foreground">{{ audience.description }}</p>
                        </CardContent>
                    </Card>
                </div>
            </section>

            <!-- Final CTA -->
            <section class="border-t">
                <div class="mx-auto max-w-6xl px-6 py-16 text-center sm:py-20">
                    <h2 class="text-2xl font-bold tracking-tight text-balance sm:text-3xl">Ready to run your first event?</h2>
                    <p class="mx-auto mt-3 max-w-xl text-muted-foreground">
                        Create your organization, set up a competition, and invite your judges &mdash; you'll be
                        collecting registrations within minutes.
                    </p>
                    <div class="mt-7 flex flex-wrap items-center justify-center gap-3">
                        <Link :href="route('register')">
                            <Button size="lg">Create your organization</Button>
                        </Link>
                        <Link :href="route('login')">
                            <Button size="lg" variant="outline">Log in</Button>
                        </Link>
                    </div>
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
