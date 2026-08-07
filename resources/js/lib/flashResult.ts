import { router } from '@inertiajs/vue3';
import { showResult } from '@/composables/useResultModal';
import type { FlashToast } from '@/types/ui';

export function initializeFlashResult(): void {
    router.on('flash', (event) => {
        const flash = (event as CustomEvent).detail?.flash;
        const data = flash?.toast as FlashToast | undefined;

        if (!data) {
            return;
        }

        if (data.type === 'success' || data.type === 'info') {
            showResult('success', data.message);
            return;
        }

        if (data.type === 'warning') {
            showResult('failure', data.message);
            return;
        }

        showResult('error', data.message || undefined);
    });
}
