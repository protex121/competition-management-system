<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { type RubricCriterionItem } from '@/types';
import { router, useForm } from '@inertiajs/vue3';
import { LoaderCircle, Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';

interface Props {
    competitionId: number;
    criteria: RubricCriterionItem[];
    canCreate: boolean;
}

const props = defineProps<Props>();

const showCreateForm = ref(false);

const createForm = useForm({
    name: '',
    description: '',
    max_score: 10 as string | number,
    sort_order: '' as string | number,
});

const submitCreate = () => {
    createForm.post(route('competitions.rubric-criteria.store', props.competitionId), {
        preserveScroll: true,
        onSuccess: () => {
            createForm.reset();
            showCreateForm.value = false;
        },
    });
};

const editingCriterionId = ref<number | null>(null);

const editForm = useForm({
    name: '',
    description: '',
    max_score: 10 as string | number,
    sort_order: '' as string | number,
});

const openEdit = (criterion: RubricCriterionItem) => {
    editingCriterionId.value = criterion.id;
    editForm.clearErrors();
    editForm.name = criterion.name;
    editForm.description = criterion.description ?? '';
    editForm.max_score = criterion.max_score;
    editForm.sort_order = criterion.sort_order;
};

const submitEdit = (criterionId: number) => {
    editForm.put(route('rubric-criteria.update', criterionId), {
        preserveScroll: true,
        onSuccess: () => {
            editingCriterionId.value = null;
        },
    });
};

const deleteCriterion = (criterion: RubricCriterionItem) => {
    if (!confirm(`Delete "${criterion.name}"? This cannot be undone.`)) {
        return;
    }

    router.delete(route('rubric-criteria.destroy', criterion.id), { preserveScroll: true });
};
</script>

<template>
    <div class="space-y-4">
        <div v-if="canCreate" class="flex justify-end">
            <Button v-if="!showCreateForm" type="button" variant="outline" size="sm" @click="showCreateForm = true">
                <Plus class="mr-2 h-4 w-4" />
                Add criterion
            </Button>
        </div>

        <form v-if="showCreateForm && canCreate" class="space-y-4 rounded-lg border p-4" @submit.prevent="submitCreate">
            <p class="text-sm font-medium">New criterion</p>
            <div class="grid gap-2">
                <Label for="create-criterion-name">Name</Label>
                <Input id="create-criterion-name" v-model="createForm.name" required />
                <InputError :message="createForm.errors.name" />
            </div>
            <div class="grid gap-2">
                <Label for="create-criterion-description">Description</Label>
                <textarea
                    id="create-criterion-description"
                    v-model="createForm.description"
                    rows="2"
                    class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                />
                <InputError :message="createForm.errors.description" />
            </div>
            <div class="grid gap-2 sm:w-48">
                <Label for="create-criterion-max">Max score</Label>
                <Input id="create-criterion-max" v-model="createForm.max_score" type="number" min="1" />
                <InputError :message="createForm.errors.max_score" />
            </div>
            <div class="flex gap-2">
                <Button type="submit" size="sm" :disabled="createForm.processing">
                    <LoaderCircle v-if="createForm.processing" class="mr-2 h-4 w-4 animate-spin" />
                    Create
                </Button>
                <Button type="button" variant="outline" size="sm" @click="showCreateForm = false">Cancel</Button>
            </div>
        </form>

        <div v-if="criteria.length === 0" class="py-4 text-center text-sm text-muted-foreground">No rubric criteria yet.</div>

        <ul v-else class="divide-y rounded-lg border">
            <li v-for="criterion in criteria" :key="criterion.id" class="p-4">
                <div v-if="editingCriterionId !== criterion.id" class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="space-y-1">
                        <p class="font-medium">{{ criterion.name }}</p>
                        <p v-if="criterion.description" class="text-xs text-muted-foreground">{{ criterion.description }}</p>
                        <p class="text-xs text-muted-foreground">Max score: {{ criterion.max_score }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <Button v-if="criterion.can.update" type="button" variant="outline" size="sm" @click="openEdit(criterion)">
                            <Pencil class="h-4 w-4" />
                        </Button>
                        <Button v-if="criterion.can.delete" type="button" variant="destructive" size="sm" @click="deleteCriterion(criterion)">
                            <Trash2 class="h-4 w-4" />
                        </Button>
                    </div>
                </div>
                <form v-else class="space-y-4" @submit.prevent="submitEdit(criterion.id)">
                    <div class="grid gap-2">
                        <Label :for="`edit-name-${criterion.id}`">Name</Label>
                        <Input :id="`edit-name-${criterion.id}`" v-model="editForm.name" required />
                        <InputError :message="editForm.errors.name" />
                    </div>
                    <div class="grid gap-2">
                        <Label :for="`edit-description-${criterion.id}`">Description</Label>
                        <textarea
                            :id="`edit-description-${criterion.id}`"
                            v-model="editForm.description"
                            rows="2"
                            class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                        />
                        <InputError :message="editForm.errors.description" />
                    </div>
                    <div class="grid gap-2 sm:w-48">
                        <Label :for="`edit-max-${criterion.id}`">Max score</Label>
                        <Input :id="`edit-max-${criterion.id}`" v-model="editForm.max_score" type="number" min="1" />
                        <InputError :message="editForm.errors.max_score" />
                    </div>
                    <div class="flex gap-2">
                        <Button type="submit" size="sm" :disabled="editForm.processing">
                            <LoaderCircle v-if="editForm.processing" class="mr-2 h-4 w-4 animate-spin" />
                            Save
                        </Button>
                        <Button type="button" variant="outline" size="sm" @click="editingCriterionId = null">Cancel</Button>
                    </div>
                </form>
            </li>
        </ul>
    </div>
</template>
