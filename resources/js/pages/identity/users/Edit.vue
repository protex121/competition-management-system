<script setup lang="ts">
import { TransitionRoot } from '@headlessui/vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type ManagedUser, type RoleOption, type UserPermissions } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

interface Props {
    user: ManagedUser;
    roles: RoleOption[];
    can: UserPermissions;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Users', href: route('users.index') },
    { title: props.user.name, href: route('users.edit', props.user.id) },
];

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    role: props.user.role,
});

const submit = () => {
    form.put(route('users.update', props.user.id), {
        preserveScroll: true,
    });
};

const deactivate = () => {
    router.patch(route('users.deactivate', props.user.id), {}, { preserveScroll: true });
};

const reactivate = () => {
    router.patch(route('users.reactivate', props.user.id), {}, { preserveScroll: true });
};

const deleteForm = useForm({});

const deleteUser = () => {
    deleteForm.delete(route('users.destroy', props.user.id));
};
</script>

<template>
    <Head :title="`Edit ${user.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">
            <div class="flex items-start justify-between gap-4">
                <Heading :title="user.name" description="Update user details and manage account status" />
                <span
                    class="inline-flex shrink-0 rounded-full px-2.5 py-0.5 text-xs font-medium"
                    :class="
                        user.deactivated_at
                            ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300'
                            : 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300'
                    "
                >
                    {{ user.deactivated_at ? 'Deactivated' : 'Active' }}
                </span>
            </div>

            <Card class="max-w-2xl">
                <CardHeader>
                    <CardTitle>Profile</CardTitle>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="grid gap-2">
                            <Label for="name">Name</Label>
                            <Input id="name" v-model="form.name" required autocomplete="name" />
                            <InputError :message="form.errors.name" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="email">Email address</Label>
                            <Input id="email" type="email" v-model="form.email" required autocomplete="email" />
                            <InputError :message="form.errors.email" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="role">Role</Label>
                            <select
                                id="role"
                                v-model="form.role"
                                required
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                            >
                                <option v-for="role in roles" :key="role.value" :value="role.value">
                                    {{ role.label }}
                                </option>
                            </select>
                            <InputError :message="form.errors.role" />
                        </div>

                        <div class="flex items-center gap-4">
                            <Button type="submit" :disabled="form.processing">
                                <LoaderCircle v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                                Save changes
                            </Button>
                            <Button as-child variant="outline">
                                <Link :href="route('users.index')">Back to list</Link>
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

            <Card v-if="can.deactivate || can.reactivate" class="max-w-2xl">
                <CardHeader>
                    <CardTitle>Account status</CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <p class="text-sm text-muted-foreground">
                        Deactivated users cannot log in but their data is preserved. You can reactivate them later.
                    </p>
                    <div class="flex gap-3">
                        <Button v-if="can.deactivate" type="button" variant="outline" @click="deactivate"> Deactivate user </Button>
                        <Button v-if="can.reactivate" type="button" @click="reactivate"> Reactivate user </Button>
                    </div>
                </CardContent>
            </Card>

            <Card v-if="can.delete" class="max-w-2xl border-red-200 dark:border-red-900/50">
                <CardHeader>
                    <CardTitle class="text-red-600 dark:text-red-400">Danger zone</CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <p class="text-sm text-muted-foreground">
                        Soft-deleting a user removes them from the organization. This action can be reversed by a platform admin.
                    </p>
                    <Dialog>
                        <DialogTrigger as-child>
                            <Button variant="destructive">Delete user</Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>Delete {{ user.name }}?</DialogTitle>
                                <DialogDescription>
                                    This will soft-delete the user account. They will no longer appear in the user list and cannot log in.
                                </DialogDescription>
                            </DialogHeader>
                            <DialogFooter>
                                <DialogClose as-child>
                                    <Button variant="secondary">Cancel</Button>
                                </DialogClose>
                                <Button variant="destructive" :disabled="deleteForm.processing" @click="deleteUser">
                                    <LoaderCircle v-if="deleteForm.processing" class="mr-2 h-4 w-4 animate-spin" />
                                    Delete user
                                </Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
