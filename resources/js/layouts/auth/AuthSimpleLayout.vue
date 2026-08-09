<script setup lang="ts">
defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="flex min-h-svh flex-col items-center justify-center gap-6 bg-background p-6 md:p-10"
    >
        <div class="w-full max-w-sm">
            <div class="flex flex-col gap-8">
                <div class="flex flex-col items-center gap-4">
                    <div class="flex flex-col items-center">
                        <img
                            src="/images/tindawatch-logo.png"
                            alt="TindaWatch"
                            class="h-28 w-auto max-w-full object-contain md:h-32"
                        />
                        <span class="sr-only">TindaWatch</span>
                    </div>
                    <div
                        v-if="title || description"
                        class="space-y-2 text-center"
                    >
                        <h1 v-if="title" class="text-xl font-medium">
                            {{ title }}
                        </h1>
                        <p
                            v-if="description"
                            class="text-center text-sm text-muted-foreground"
                        >
                            {{ description }}
                        </p>
                    </div>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
