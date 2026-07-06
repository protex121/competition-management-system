<script setup lang="ts">
import { TransitionRoot } from '@headlessui/vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

interface Profile {
    bio: string | null;
    phone: string | null;
    institution: string | null;
}

interface Props {
    profile: Profile | null;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Participant profile', href: route('participant.profile.edit') },
];

const form = useForm({
    bio: props.profile?.bio ?? '',
    phone: props.profile?.phone ?? '',
    institution: props.profile?.institution ?? '',
});

const submit = () => {
    form.put(route('participant.profile.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Participant profile" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">
            <Heading title="Participant profile" description="Information used when joining teams and competitions" />

            <Card class="max-w-2xl">
                <CardHeader>
                    <CardTitle>Profile details</CardTitle>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="grid gap-2">
                            <Label for="bio">Bio</Label>
                            <textarea
                                id="bio"
                                v-model="form.bio"
                                rows="4"
                                class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                placeholder="Short introduction"
                            />
                            <InputError :message="form.errors.bio" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="phone">Phone</Label>
                            <Input id="phone" v-model="form.phone" placeholder="+62..." />
                            <InputError :message="form.errors.phone" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="institution">Institution</Label>
                            <Input id="institution" v-model="form.institution" placeholder="School or company" />
                            <InputError :message="form.errors.institution" />
                        </div>

                        <div class="flex items-center gap-4">
                            <Button type="submit" :disabled="form.processing">
                                <LoaderCircle v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                                Save profile
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
        </div>
    </AppLayout>
</template>
