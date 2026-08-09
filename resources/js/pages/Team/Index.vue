<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import { ref } from 'vue';
import TeamController from '@/actions/App/Http/Controllers/TeamController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index as teamIndex } from '@/routes/team';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Team', href: teamIndex() }],
    },
});

defineProps<{
    members: Array<{
        id: number;
        name: string;
        email: string;
        role: string;
    }>;
    roles: Array<{ value: string; label: string }>;
}>();

const createOpen = ref(false);
</script>

<template>
    <Head title="Team" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <div
            class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
        >
            <Heading title="Team" />
            <Button @click="createOpen = true">
                <Plus />
                Add member
            </Button>
        </div>

        <div class="overflow-x-auto rounded-xl border">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b bg-muted/40 text-left">
                        <th class="px-3 py-2 font-medium">Name</th>
                        <th class="px-3 py-2 font-medium">Email</th>
                        <th class="px-3 py-2 font-medium">Role</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="member in members"
                        :key="member.id"
                        class="border-b"
                    >
                        <td class="px-3 py-2 font-medium">{{ member.name }}</td>
                        <td class="px-3 py-2">{{ member.email }}</td>
                        <td class="px-3 py-2">
                            <Badge variant="secondary">{{ member.role }}</Badge>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Dialog v-model:open="createOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Add member</DialogTitle>
                </DialogHeader>
                <Form
                    v-bind="TeamController.store.form()"
                    class="space-y-4"
                    v-slot="{ errors, processing }"
                    @success="createOpen = false"
                >
                    <div class="grid gap-2">
                        <Label for="member-name">Name</Label>
                        <Input id="member-name" name="name" required />
                        <InputError :message="errors.name" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="member-email">Email</Label>
                        <Input
                            id="member-email"
                            name="email"
                            type="email"
                            required
                        />
                        <InputError :message="errors.email" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="member-password">Password</Label>
                        <Input
                            id="member-password"
                            name="password"
                            type="password"
                            required
                        />
                        <InputError :message="errors.password" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="member-role">Role</Label>
                        <select
                            id="member-role"
                            name="role"
                            class="h-9 rounded-md border bg-background px-2 text-sm"
                            required
                        >
                            <option
                                v-for="role in roles"
                                :key="role.value"
                                :value="role.value"
                            >
                                {{ role.label }}
                            </option>
                        </select>
                        <InputError :message="errors.role" />
                    </div>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            @click="createOpen = false"
                            >Cancel</Button
                        >
                        <Button type="submit" :disabled="processing">
                            <Plus />
                            Add
                        </Button>
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>
    </div>
</template>
