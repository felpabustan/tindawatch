import { computed, ref } from 'vue';

export type ResultKind = 'success' | 'failure' | 'error';

const open = ref(false);
const kind = ref<ResultKind>('success');
const title = ref('Success!');
const subtitle = ref<string | null>(null);
let closeTimer: ReturnType<typeof setTimeout> | null = null;

const DEFAULT_TITLES: Record<ResultKind, string> = {
    success: 'Success!',
    failure: 'Failed to process',
    error: 'Oops, there something wrong',
};

export function showResult(
    resultKind: ResultKind,
    resultTitle?: string,
    resultSubtitle?: string | null,
): void {
    if (closeTimer) {
        clearTimeout(closeTimer);
        closeTimer = null;
    }

    kind.value = resultKind;
    title.value = resultTitle?.trim() || DEFAULT_TITLES[resultKind];
    subtitle.value = resultSubtitle?.trim() || null;
    open.value = true;

    closeTimer = setTimeout(() => {
        open.value = false;
        closeTimer = null;
    }, 2500);
}

export function useResultModal() {
    const description = computed(() => {
        switch (kind.value) {
            case 'success':
                return 'The action completed successfully.';
            case 'failure':
                return 'The action could not be completed. Please try again.';
            case 'error':
                return 'Something unexpected happened. Please try again.';
        }
    });

    return {
        open,
        kind,
        title,
        subtitle,
        description,
        showResult,
    };
}
