<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
</script>

<template>
    <!-- Full wordmark when sidebar is expanded; mark when collapsed -->
    <img
        src="/images/tindawatch-logo.png"
        alt="TindaWatch"
        class="h-16 w-auto max-w-full object-contain object-left group-data-[collapsible=icon]:hidden"
    />
    <AppLogoIcon class="hidden size-8 group-data-[collapsible=icon]:block" />
</template>
