<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
import { ArrowLeftRight, Pencil, Plus } from '@lucide/vue';
import { ref } from 'vue';
import StoreController from '@/actions/App/Http/Controllers/StoreController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index as storesIndex } from '@/routes/stores';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Stores', href: storesIndex() }],
    },
});

defineProps<{
    stores: Array<{
        id: number;
        name: string;
        address: string | null;
        role: string;
        is_current: boolean;
        is_owner: boolean;
    }>;
    maxStores: number;
    ownedCount: number;
    canCreateStore: boolean;
}>();

const createOpen = ref(false);
const editStore = ref<{
    id: number;
    name: string;
    address: string | null;
} | null>(null);

function switchStore(id: number) {
    router.post(StoreController.switch.url(id));
}

function openEdit(store: { id: number; name: string; address: string | null }) {
    editStore.value = { ...store };
}
</script>

<template>
    <Head title="Stores" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <Heading title="Stores" />
            <div class="flex items-center gap-2">
                <Badge variant="secondary">{{ ownedCount }}/{{ maxStores }}</Badge>
                <Button
                    v-if="canCreateStore"
                    @click="createOpen = true"
                >
                    <Plus />
                    Add store
                </Button>
                <p
                    v-else
                    class="text-sm text-muted-foreground"
                >
                    Limit reached
                </p>
            </div>
        </div>

        <div class="grid gap-3">
            <div
                v-for="store in stores"
                :key="store.id"
                class="flex flex-col gap-3 rounded-xl border p-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="font-medium">{{ store.name }}</h3>
                        <Badge v-if="store.is_current">Current</Badge>
                        <Badge variant="secondary">{{ store.role }}</Badge>
                    </div>
                    <p v-if="store.address" class="text-sm text-muted-foreground">
                        {{ store.address }}
                    </p>
                </div>
                <div class="flex gap-1">
                    <Button
                        v-if="store.is_owner"
                        variant="outline"
                        size="icon-sm"
                        title="Edit"
                        @click="openEdit(store)"
                    >
                        <Pencil />
                    </Button>
                    <Button
                        v-if="!store.is_current"
                        variant="outline"
                        size="icon-sm"
                        title="Switch"
                        @click="switchStore(store.id)"
                    >
                        <ArrowLeftRight />
                    </Button>
                </div>
            </div>
        </div>

        <Dialog v-model:open="createOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Add store</DialogTitle>
                    <DialogDescription>
                        {{ ownedCount }}/{{ maxStores }} used
                    </DialogDescription>
                </DialogHeader>
                <Form
                    v-bind="StoreController.store.form()"
                    class="space-y-4"
                    v-slot="{ errors, processing }"
                    @success="createOpen = false"
                >
                    <div class="grid gap-2">
                        <Label for="create-name">Store name</Label>
                        <Input id="create-name" name="name" required />
                        <InputError :message="errors.name" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="create-address">Address</Label>
                        <Input id="create-address" name="address" />
                        <InputError :message="errors.address" />
                    </div>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            @click="createOpen = false"
                        >
                            Cancel
                        </Button>
                        <Button type="submit" :disabled="processing">
                            <Plus />
                            Create
                        </Button>
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>

        <Dialog
            :open="!!editStore"
            @update:open="(open) => !open && (editStore = null)"
        >
            <DialogContent v-if="editStore">
                <DialogHeader>
                    <DialogTitle>Edit store</DialogTitle>
                </DialogHeader>
                <Form
                    v-bind="StoreController.update.form(editStore.id)"
                    class="space-y-4"
                    v-slot="{ errors, processing }"
                    @success="editStore = null"
                >
                    <div class="grid gap-2">
                        <Label for="edit-name">Store name</Label>
                        <Input
                            id="edit-name"
                            name="name"
                            :default-value="editStore.name"
                            required
                        />
                        <InputError :message="errors.name" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="edit-address">Address</Label>
                        <Input
                            id="edit-address"
                            name="address"
                            :default-value="editStore.address ?? ''"
                        />
                        <InputError :message="errors.address" />
                    </div>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            @click="editStore = null"
                        >
                            Cancel
                        </Button>
                        <Button type="submit" :disabled="processing"
                            >Save</Button
                        >
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>
    </div>
</template>
