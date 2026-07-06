<script setup lang="ts">
import DateTimePicker from '@/components/DateTimePicker.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
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
import { type ManagedCategory } from '@/types';
import { router, useForm } from '@inertiajs/vue3';
import { LoaderCircle, Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';

interface Props {
    competitionId: number;
    categories: ManagedCategory[];
    canCreate: boolean;
}

const props = defineProps<Props>();

const showCreateForm = ref(false);

const createForm = useForm({
    name: '',
    slug: '',
    description: '',
    max_participants: '' as string | number,
    registration_ends_at: '',
    sort_order: '' as string | number,
});

const submitCreate = () => {
    createForm.post(route('competitions.categories.store', props.competitionId), {
        preserveScroll: true,
        onSuccess: () => {
            createForm.reset();
            showCreateForm.value = false;
        },
    });
};

const editingCategory = ref<ManagedCategory | null>(null);

const editForm = useForm({
    name: '',
    slug: '',
    description: '',
    max_participants: '' as string | number,
    registration_ends_at: '',
    sort_order: '' as string | number,
});

const toDatetimeLocal = (value: string | null | undefined): string => (value ? value.slice(0, 16) : '');

const openEdit = (category: ManagedCategory) => {
    editingCategory.value = category;
    editForm.clearErrors();
    editForm.name = category.name;
    editForm.slug = category.slug;
    editForm.description = category.description ?? '';
    editForm.max_participants = category.max_participants ?? '';
    editForm.registration_ends_at = toDatetimeLocal(category.registration_ends_at);
    editForm.sort_order = category.sort_order;
};

const submitEdit = () => {
    if (!editingCategory.value) {
        return;
    }

    editForm.put(
        route('competitions.categories.update', {
            competition: props.competitionId,
            category: editingCategory.value.id,
        }),
        {
            preserveScroll: true,
            onSuccess: () => {
                editingCategory.value = null;
            },
        },
    );
};

const deleteCategory = (category: ManagedCategory) => {
    router.delete(
        route('competitions.categories.destroy', {
            competition: props.competitionId,
            category: category.id,
        }),
        { preserveScroll: true },
    );
};

const activateCategory = (category: ManagedCategory) => {
    router.patch(
        route('competitions.categories.activate', {
            competition: props.competitionId,
            category: category.id,
        }),
        {},
        { preserveScroll: true },
    );
};

const disableCategory = (category: ManagedCategory) => {
    router.patch(
        route('competitions.categories.disable', {
            competition: props.competitionId,
            category: category.id,
        }),
        {},
        { preserveScroll: true },
    );
};

const formatStatus = (status: string): string =>
    status
        .split('-')
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');

const statusClass = (status: string): string => {
    switch (status) {
        case 'active':
            return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300';
        case 'disabled':
            return 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-300';
        case 'archived':
            return 'bg-slate-100 text-slate-800 dark:bg-slate-900/30 dark:text-slate-300';
        default:
            return 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300';
    }
};
</script>

<template>
    <div class="space-y-4">
        <div v-if="canCreate" class="flex justify-end">
            <Button v-if="!showCreateForm" type="button" variant="outline" size="sm" @click="showCreateForm = true">
                <Plus class="mr-2 h-4 w-4" />
                Add category
            </Button>
        </div>

        <form v-if="showCreateForm && canCreate" class="space-y-4 rounded-lg border p-4" @submit.prevent="submitCreate">
            <p class="text-sm font-medium">New category</p>
            <div class="grid gap-2">
                <Label for="create-name">Name</Label>
                <Input id="create-name" v-model="createForm.name" required />
                <InputError :message="createForm.errors.name" />
            </div>
            <div class="grid gap-2">
                <Label for="create-slug">Slug (optional)</Label>
                <Input id="create-slug" v-model="createForm.slug" placeholder="auto-generated from name" />
                <InputError :message="createForm.errors.slug" />
            </div>
            <div class="grid gap-2">
                <Label for="create-description">Description</Label>
                <textarea
                    id="create-description"
                    v-model="createForm.description"
                    rows="2"
                    class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                />
                <InputError :message="createForm.errors.description" />
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="create-max">Max participants</Label>
                    <Input id="create-max" v-model="createForm.max_participants" type="number" min="1" />
                    <InputError :message="createForm.errors.max_participants" />
                </div>
                <div class="grid gap-2">
                    <Label for="create-registration-ends">Registration closes</Label>
                    <DateTimePicker id="create-registration-ends" v-model="createForm.registration_ends_at" />
                    <InputError :message="createForm.errors.registration_ends_at" />
                </div>
            </div>
            <div class="flex gap-2">
                <Button type="submit" size="sm" :disabled="createForm.processing">
                    <LoaderCircle v-if="createForm.processing" class="mr-2 h-4 w-4 animate-spin" />
                    Create
                </Button>
                <Button type="button" variant="outline" size="sm" @click="showCreateForm = false">Cancel</Button>
            </div>
        </form>

        <div v-if="categories.length === 0" class="py-4 text-center text-sm text-muted-foreground">No categories yet.</div>

        <ul v-else class="divide-y rounded-lg border">
            <li v-for="category in categories" :key="category.id" class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <span class="font-medium">{{ category.name }}</span>
                        <span v-if="category.is_default" class="text-xs text-muted-foreground">(default)</span>
                    </div>
                    <p class="text-xs text-muted-foreground">{{ category.slug }}</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium" :class="statusClass(category.status)">
                        {{ formatStatus(category.status) }}
                    </span>
                    <Dialog v-if="category.can.update" :open="editingCategory?.id === category.id" @update:open="(open) => !open && (editingCategory = null)">
                        <DialogTrigger as-child>
                            <Button type="button" variant="outline" size="sm" @click="openEdit(category)">
                                <Pencil class="h-4 w-4" />
                            </Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>Edit {{ category.name }}</DialogTitle>
                            </DialogHeader>
                            <form class="space-y-4" @submit.prevent="submitEdit">
                                <div class="grid gap-2">
                                    <Label for="edit-name">Name</Label>
                                    <Input id="edit-name" v-model="editForm.name" required />
                                    <InputError :message="editForm.errors.name" />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="edit-slug">Slug</Label>
                                    <Input id="edit-slug" v-model="editForm.slug" required />
                                    <InputError :message="editForm.errors.slug" />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="edit-description">Description</Label>
                                    <textarea
                                        id="edit-description"
                                        v-model="editForm.description"
                                        rows="2"
                                        class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                    />
                                    <InputError :message="editForm.errors.description" />
                                </div>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div class="grid gap-2">
                                        <Label for="edit-max">Max participants</Label>
                                        <Input id="edit-max" v-model="editForm.max_participants" type="number" min="1" />
                                        <InputError :message="editForm.errors.max_participants" />
                                    </div>
                                    <div class="grid gap-2">
                                        <Label for="edit-registration-ends">Registration closes</Label>
                                        <DateTimePicker id="edit-registration-ends" v-model="editForm.registration_ends_at" />
                                        <InputError :message="editForm.errors.registration_ends_at" />
                                    </div>
                                </div>
                                <DialogFooter>
                                    <DialogClose as-child>
                                        <Button type="button" variant="secondary">Cancel</Button>
                                    </DialogClose>
                                    <Button type="submit" :disabled="editForm.processing">
                                        <LoaderCircle v-if="editForm.processing" class="mr-2 h-4 w-4 animate-spin" />
                                        Save
                                    </Button>
                                </DialogFooter>
                            </form>
                        </DialogContent>
                    </Dialog>
                    <Button v-if="category.can.activate" type="button" variant="outline" size="sm" @click="activateCategory(category)">
                        Activate
                    </Button>
                    <Button v-if="category.can.disable" type="button" variant="outline" size="sm" @click="disableCategory(category)">
                        Disable
                    </Button>
                    <Dialog v-if="category.can.delete">
                        <DialogTrigger as-child>
                            <Button type="button" variant="destructive" size="sm">
                                <Trash2 class="h-4 w-4" />
                            </Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>Delete {{ category.name }}?</DialogTitle>
                                <DialogDescription>This will soft-delete the category.</DialogDescription>
                            </DialogHeader>
                            <DialogFooter>
                                <DialogClose as-child>
                                    <Button variant="secondary">Cancel</Button>
                                </DialogClose>
                                <Button variant="destructive" @click="deleteCategory(category)">Delete</Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>
                </div>
            </li>
        </ul>
    </div>
</template>
