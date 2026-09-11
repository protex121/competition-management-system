<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import LocaleSwitcher from '@/components/LocaleSwitcher.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { useTranslation } from '@/composables/useTranslation';
import { Head, Link } from '@inertiajs/vue3';
import { Clock, Eye, Gavel, ListChecks, Lock, ShieldCheck, SlidersHorizontal, Trophy, UploadCloud, UserCog, UserRoundCheck, Users2 } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, useTemplateRef } from 'vue';

const { t } = useTranslation();

interface Stage {
    icon: typeof Trophy;
    title: string;
    description: string;
}

const stages = computed<Stage[]>(() => [
    {
        icon: Users2,
        title: t('landing.stage_register'),
        description: t('landing.stage_register_description'),
    },
    {
        icon: UploadCloud,
        title: t('landing.stage_submit'),
        description: t('landing.stage_submit_description'),
    },
    {
        icon: Gavel,
        title: t('landing.stage_judge'),
        description: t('landing.stage_judge_description'),
    },
    {
        icon: Trophy,
        title: t('landing.stage_rank'),
        description: t('landing.stage_rank_description'),
    },
]);

interface Audience {
    icon: typeof Trophy;
    title: string;
    description: string;
}

const audiences = computed<Audience[]>(() => [
    {
        icon: UserCog,
        title: t('landing.role_organizers'),
        description: t('landing.role_organizers_description'),
    },
    {
        icon: UserRoundCheck,
        title: t('landing.role_participants'),
        description: t('landing.role_participants_description'),
    },
    {
        icon: ShieldCheck,
        title: t('landing.role_judges'),
        description: t('landing.role_judges_description'),
    },
]);

interface Point {
    icon: typeof Trophy;
    text: string;
}

const judgingPoints = computed<Point[]>(() => [
    { icon: ListChecks, text: t('landing.judges_point_rubric') },
    { icon: ShieldCheck, text: t('landing.judges_point_self') },
    { icon: Lock, text: t('landing.judges_point_lock') },
]);

const organizerPoints = computed<Point[]>(() => [
    { icon: UserCog, text: t('landing.organizers_point_assign') },
    { icon: SlidersHorizontal, text: t('landing.organizers_point_rubric') },
    { icon: Clock, text: t('landing.organizers_point_windows') },
    { icon: Eye, text: t('landing.organizers_point_oversight') },
]);

const heroIllustration = useTemplateRef<HTMLElement>('heroIllustration');
const judgingIllustration = useTemplateRef<HTMLElement>('judgingIllustration');
const organizerIllustration = useTemplateRef<HTMLElement>('organizerIllustration');

let observer: IntersectionObserver | undefined;

onMounted(() => {
    const targets = [heroIllustration.value, judgingIllustration.value, organizerIllustration.value].filter(
        (el): el is HTMLElement => el !== null,
    );

    observer = new IntersectionObserver(
        (entries) => {
            for (const entry of entries) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer?.unobserve(entry.target);
                }
            }
        },
        { threshold: 0.35 },
    );

    targets.forEach((el) => observer?.observe(el));
});

onUnmounted(() => observer?.disconnect());
</script>

<template>
    <Head :title="t('landing.page_title')" />

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
                    <LocaleSwitcher />
                    <Link v-if="$page.props.auth.user" :href="route('dashboard')">
                        <Button size="sm">{{ t('nav.dashboard') }}</Button>
                    </Link>
                    <template v-else>
                        <Link
                            :href="route('login')"
                            class="whitespace-nowrap text-sm text-muted-foreground transition-colors hover:text-foreground"
                            >{{ t('common.log_in') }}</Link
                        >
                        <Link :href="route('register')">
                            <Button size="sm">{{ t('landing.get_started') }}</Button>
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
                        {{ t('landing.tagline') }}
                    </p>
                    <h1 class="text-4xl font-bold tracking-tight text-balance sm:text-5xl">
                        {{ t('landing.hero_title') }}
                    </h1>
                    <p class="mt-5 text-lg text-muted-foreground text-balance">
                        {{ t('landing.hero_description') }}
                    </p>
                    <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                        <Link :href="route('register')">
                            <Button size="lg">{{ t('landing.create_organization') }}</Button>
                        </Link>
                        <Link :href="route('login')">
                            <Button size="lg" variant="outline">{{ t('common.log_in') }}</Button>
                        </Link>
                    </div>
                </div>

                <!-- Hero illustration -->
                <div class="mx-auto mt-16 max-w-3xl">
                    <div ref="heroIllustration" class="illustration flex items-center justify-center rounded-xl border bg-muted/30 px-6 py-14 sm:py-20">
                        <svg viewBox="0 0 480 260" class="h-auto w-full max-w-md" fill="none" aria-hidden="true">
                            <line x1="30" y1="234" x2="450" y2="234" class="stroke-border" stroke-width="2" />
                            <rect x="88" y="130" width="84" height="104" rx="10" class="podium-bar podium-bar-2 fill-muted" />
                            <circle cx="130" cy="110" r="19" class="podium-badge podium-badge-2 fill-background stroke-border" stroke-width="2" />
                            <text x="130" y="117" text-anchor="middle" class="podium-badge podium-badge-2 fill-foreground" font-size="16" font-weight="700">2</text>
                            <rect x="198" y="78" width="84" height="156" rx="10" class="podium-bar podium-bar-1 fill-foreground" />
                            <circle cx="240" cy="58" r="22" class="podium-badge podium-badge-1 fill-foreground stroke-border" stroke-width="2" />
                            <text x="240" y="65" text-anchor="middle" class="podium-badge podium-badge-1 fill-background" font-size="18" font-weight="700">1</text>
                            <rect x="308" y="164" width="84" height="70" rx="10" class="podium-bar podium-bar-3 fill-muted" />
                            <circle cx="350" cy="144" r="19" class="podium-badge podium-badge-3 fill-background stroke-border" stroke-width="2" />
                            <text x="350" y="151" text-anchor="middle" class="podium-badge podium-badge-3 fill-foreground" font-size="16" font-weight="700">3</text>
                        </svg>
                    </div>
                    <p class="mt-3 text-center text-sm text-muted-foreground">
                        {{ t('landing.hero_caption') }}
                    </p>
                </div>
            </section>

            <!-- Pipeline -->
            <section class="mt-20 border-y bg-muted/40 sm:mt-28">
                <div class="mx-auto max-w-6xl px-6 py-16">
                    <h2 class="text-center text-2xl font-bold tracking-tight">{{ t('landing.pipeline_title') }}</h2>
                    <p class="mx-auto mt-2 max-w-2xl text-center text-muted-foreground">
                        {{ t('landing.pipeline_description') }}
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
                        <p class="text-xs font-semibold uppercase tracking-widest text-muted-foreground">{{ t('landing.judges_eyebrow') }}</p>
                        <h2 class="mt-3 text-2xl font-bold tracking-tight text-balance sm:text-3xl">{{ t('landing.judges_title') }}</h2>
                        <p class="mt-3 text-muted-foreground">
                            {{ t('landing.judges_description') }}
                        </p>
                        <ul class="mt-6 space-y-4">
                            <li v-for="point in judgingPoints" :key="point.text" class="flex gap-3">
                                <component :is="point.icon" class="mt-0.5 size-5 shrink-0 text-muted-foreground" />
                                <span class="text-sm text-foreground/90">{{ point.text }}</span>
                            </li>
                        </ul>
                    </div>
                    <div ref="judgingIllustration" class="illustration flex items-center justify-center rounded-xl border bg-muted/30 px-6 py-12">
                        <svg viewBox="0 0 480 320" class="h-auto w-full max-w-sm" fill="none" aria-hidden="true">
                            <rect x="90" y="36" width="300" height="256" rx="16" class="fill-card stroke-border" stroke-width="2" />
                            <rect x="195" y="20" width="90" height="26" rx="8" class="fill-muted stroke-border" stroke-width="2" />
                            <g v-for="(row, idx) in [0, 1, 2]" :key="row" :class="`score-row score-row-${idx + 1}`">
                                <rect :y="88 + idx * 62" x="120" width="130" height="10" rx="5" class="fill-muted-foreground/35" />
                                <rect :y="108 + idx * 62" x="120" width="240" height="10" rx="5" class="fill-muted" />
                                <rect
                                    :y="108 + idx * 62"
                                    x="120"
                                    :width="idx === 0 ? 200 : idx === 1 ? 150 : 220"
                                    height="10"
                                    rx="5"
                                    :class="`score-fill score-fill-${idx + 1} fill-foreground`"
                                />
                            </g>
                            <circle cx="342" cy="254" r="26" class="checkmark-badge fill-foreground" />
                            <path
                                d="M330 254l8 8 16-17"
                                class="checkmark-path stroke-background"
                                stroke-width="3"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>
                    </div>
                </div>
            </section>

            <!-- Organizer control split -->
            <section class="border-t bg-muted/40">
                <div class="mx-auto max-w-6xl px-6 py-16 sm:py-24">
                    <div class="grid items-center gap-10 lg:grid-cols-2 lg:gap-16">
                        <div
                            ref="organizerIllustration"
                            class="illustration flex items-center justify-center rounded-xl border bg-muted/30 px-6 py-12 lg:order-2"
                        >
                            <svg viewBox="0 0 480 320" class="h-auto w-full max-w-sm" fill="none" aria-hidden="true">
                                <rect x="60" y="30" width="360" height="260" rx="16" class="fill-card stroke-border" stroke-width="2" />
                                <g v-for="(row, idx) in [0, 1, 2]" :key="row" :class="`org-row org-row-${idx + 1}`">
                                    <circle :cy="88 + idx * 62" cx="104" r="16" class="fill-muted" />
                                    <rect :y="82 + idx * 62" x="136" width="140" height="12" rx="6" class="fill-muted-foreground/35" />
                                    <rect :y="78 + idx * 62" x="336" width="44" height="22" rx="11" :class="idx !== 1 ? 'fill-foreground' : 'fill-muted'" />
                                    <circle
                                        :cy="89 + idx * 62"
                                        :cx="idx !== 1 ? 369 : 347"
                                        r="8"
                                        :class="idx !== 1 ? `toggle-knob toggle-knob-${idx + 1}` : ''"
                                        class="fill-background"
                                    />
                                </g>
                            </svg>
                        </div>
                        <div class="lg:order-1">
                            <p class="text-xs font-semibold uppercase tracking-widest text-muted-foreground">{{ t('landing.organizers_eyebrow') }}</p>
                            <h2 class="mt-3 text-2xl font-bold tracking-tight text-balance sm:text-3xl">
                                {{ t('landing.organizers_title') }}
                            </h2>
                            <p class="mt-3 text-muted-foreground">
                                {{ t('landing.organizers_description') }}
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
                <h2 class="text-center text-2xl font-bold tracking-tight">{{ t('landing.roles_title') }}</h2>
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
                    <h2 class="text-2xl font-bold tracking-tight text-balance sm:text-3xl">{{ t('landing.cta_title') }}</h2>
                    <p class="mx-auto mt-3 max-w-xl text-muted-foreground">
                        {{ t('landing.cta_description') }}
                    </p>
                    <div class="mt-7 flex flex-wrap items-center justify-center gap-3">
                        <Link :href="route('register')">
                            <Button size="lg">{{ t('landing.create_organization') }}</Button>
                        </Link>
                        <Link :href="route('login')">
                            <Button size="lg" variant="outline">{{ t('common.log_in') }}</Button>
                        </Link>
                    </div>
                </div>
            </section>
        </main>

        <footer class="border-t">
            <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-3 px-6 py-8 text-sm text-muted-foreground sm:flex-row">
                <span>{{ $page.props.name }}</span>
                <div class="flex items-center gap-4">
                    <Link :href="route('login')" class="transition-colors hover:text-foreground">{{ t('common.log_in') }}</Link>
                    <Link :href="route('register')" class="transition-colors hover:text-foreground">{{ t('common.sign_up') }}</Link>
                </div>
            </div>
        </footer>
    </div>
</template>

<style scoped>
@keyframes grow-up {
    from {
        transform: scaleY(0);
    }
    to {
        transform: scaleY(1);
    }
}
@keyframes pop-in {
    from {
        opacity: 0;
        transform: scale(0.5);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}
@keyframes fade-slide-in {
    from {
        opacity: 0;
        transform: translateX(-8px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}
@keyframes fill-bar {
    from {
        transform: scaleX(0);
    }
    to {
        transform: scaleX(1);
    }
}
@keyframes badge-pop {
    from {
        opacity: 0;
        transform: scale(0.6);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}
@keyframes draw-check {
    from {
        stroke-dashoffset: 40;
    }
    to {
        stroke-dashoffset: 0;
    }
}
@keyframes slide-knob {
    from {
        transform: translateX(-22px);
    }
    to {
        transform: translateX(0);
    }
}

/* Resting (pre-animation) states */
.illustration .podium-bar {
    transform: scaleY(0);
    transform-origin: bottom;
    transform-box: fill-box;
}
.illustration .podium-badge {
    opacity: 0;
    transform: scale(0.5);
    transform-origin: center;
    transform-box: fill-box;
}
.illustration .score-row,
.illustration .org-row {
    opacity: 0;
    transform: translateX(-8px);
}
.illustration .score-fill {
    transform: scaleX(0);
    transform-origin: left;
    transform-box: fill-box;
}
.illustration .checkmark-badge {
    opacity: 0;
    transform: scale(0.6);
    transform-origin: center;
    transform-box: fill-box;
}
.illustration .checkmark-path {
    stroke-dasharray: 40;
    stroke-dashoffset: 40;
}
.illustration .toggle-knob {
    transform: translateX(-22px);
}

/* Triggered once the illustration scrolls into view */
.illustration.is-visible .podium-bar-1 {
    animation: grow-up 0.55s cubic-bezier(0.22, 1, 0.36, 1) forwards;
}
.illustration.is-visible .podium-bar-2 {
    animation: grow-up 0.55s cubic-bezier(0.22, 1, 0.36, 1) 0.15s forwards;
}
.illustration.is-visible .podium-bar-3 {
    animation: grow-up 0.55s cubic-bezier(0.22, 1, 0.36, 1) 0.3s forwards;
}
.illustration.is-visible .podium-badge-1 {
    animation: pop-in 0.3s ease-out 0.55s forwards;
}
.illustration.is-visible .podium-badge-2 {
    animation: pop-in 0.3s ease-out 0.7s forwards;
}
.illustration.is-visible .podium-badge-3 {
    animation: pop-in 0.3s ease-out 0.85s forwards;
}

.illustration.is-visible .score-row-1 {
    animation: fade-slide-in 0.4s ease-out forwards;
}
.illustration.is-visible .score-row-2 {
    animation: fade-slide-in 0.4s ease-out 0.15s forwards;
}
.illustration.is-visible .score-row-3 {
    animation: fade-slide-in 0.4s ease-out 0.3s forwards;
}
.illustration.is-visible .score-fill-1 {
    animation: fill-bar 0.45s ease-out 0.25s forwards;
}
.illustration.is-visible .score-fill-2 {
    animation: fill-bar 0.45s ease-out 0.4s forwards;
}
.illustration.is-visible .score-fill-3 {
    animation: fill-bar 0.45s ease-out 0.55s forwards;
}
.illustration.is-visible .checkmark-badge {
    animation: badge-pop 0.3s ease-out 0.8s forwards;
}
.illustration.is-visible .checkmark-path {
    animation: draw-check 0.3s ease-out 0.95s forwards;
}

.illustration.is-visible .org-row-1 {
    animation: fade-slide-in 0.4s ease-out forwards;
}
.illustration.is-visible .org-row-2 {
    animation: fade-slide-in 0.4s ease-out 0.15s forwards;
}
.illustration.is-visible .org-row-3 {
    animation: fade-slide-in 0.4s ease-out 0.3s forwards;
}
.illustration.is-visible .toggle-knob-1 {
    animation: slide-knob 0.3s ease-out 0.3s forwards;
}
.illustration.is-visible .toggle-knob-3 {
    animation: slide-knob 0.3s ease-out 0.6s forwards;
}

@media (prefers-reduced-motion: reduce) {
    .illustration .podium-bar,
    .illustration .podium-badge,
    .illustration .score-row,
    .illustration .score-fill,
    .illustration .checkmark-badge,
    .illustration .org-row,
    .illustration .toggle-knob {
        animation: none !important;
        opacity: 1 !important;
        transform: none !important;
    }
    .illustration .checkmark-path {
        animation: none !important;
        stroke-dashoffset: 0 !important;
    }
}
</style>
