<script setup lang="ts">
import { Check, X } from '@lucide/vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
} from '@/components/ui/dialog';
import { useResultModal } from '@/composables/useResultModal';

const { open, kind, title, subtitle, description } = useResultModal();
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-xs" :show-close-button="false">
            <div class="flex flex-col items-center gap-4 py-4 text-center">
                <div
                    class="flex size-20 items-center justify-center rounded-full shadow-sm"
                    :class="
                        kind === 'success' ? 'bg-emerald-500' : 'bg-red-500'
                    "
                >
                    <Check
                        v-if="kind === 'success'"
                        class="size-10 text-white"
                        :stroke-width="3"
                    />
                    <X
                        v-else
                        class="size-10 text-white"
                        :stroke-width="3"
                    />
                </div>
                <div class="space-y-1">
                    <DialogTitle class="text-xl font-semibold">
                        {{ title }}
                    </DialogTitle>
                    <p
                        v-if="subtitle"
                        class="text-lg font-medium text-muted-foreground"
                    >
                        {{ subtitle }}
                    </p>
                </div>
                <DialogDescription class="sr-only">
                    {{ description }}
                </DialogDescription>
            </div>
        </DialogContent>
    </Dialog>
</template>
