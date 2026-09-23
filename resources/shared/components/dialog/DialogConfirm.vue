<script setup lang="ts">
import Dialog from './Dialog.vue';
import { EmptyState, Button } from '@shared/components';
import { AlertDiamondIcon } from '@adersolutions/icons';
import { useInertia } from '@shared/hooks';

// Define emits
const emit = defineEmits(['close', 'confirm']);

// Define props
const props = defineProps({
    show: Boolean,
    title: String,
    message: String,
    confirmText: String,
    cancelText: String,
    icon: { type: [Function, null], default: null },
    href: String,
    variant: String,
    dark: Boolean,
    btn: Object,
    loading: Boolean,
    disabled: Boolean,
});
const router = useInertia({});

// Close event handler
const close = () => emit('close');

// Delete confirmation handler
const onConfirmedDelete = () => {
    if (props.href) {
        router.delete(props.href, {
            onSuccess: close,
        });
    }
    emit('confirm');
};
</script>

<template>
    <Dialog :show="show" max-width="xs" :title="title || 'Confirmation'" z-index="z-[911]" @close="close">
        <!-- Slot for custom content -->
        <slot name="content">
            <EmptyState :image="icon || AlertDiamondIcon" heading="Êtes-vous sûr(e) ?" :class="['py-10 bg-white text-red-500']">
                <slot />
            </EmptyState>
        </slot>
        <!-- custom plain message prop -->
        <div v-if="props.message" class="px-6 py-4 text-center text-gray-700">
            {{ props.message }}
        </div>

        <!-- Footer buttons -->
        <div class="flex flex-row justify-end gap-3 p-3 md:px-6 md:py-2 bg-gray-100 text-right">
            <Button variant="secondary" @click="close">
                {{ props.cancelText || 'Fermer' }}
            </Button>
            <Button
                class="ml-3"
                type="button"
                :variant="variant || 'danger'"
                :loading="loading || router.processing"
                :disabled="disabled"
                @click="onConfirmedDelete"
            >
                {{ props.confirmText || props.btn?.title || 'Oui, Confirmer' }}
            </Button>
        </div>
    </Dialog>
</template>
