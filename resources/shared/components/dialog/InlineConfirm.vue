<script setup lang="ts">
import { AlertTriangleIcon } from '@adersolutions/icons';
import { Button, Popup } from '@shared/components';

// Define props and types
type PropsType = {
    loading?: boolean;
    dark?: boolean;
    content?: string;
    confirmText?: string;
};
defineProps<PropsType>();

// Define emits
defineEmits<{
    (event: 'confirm', close: () => void): void;
}>();
</script>

<template>
    <Popup as="div" :dark="dark" position="top">
        <slot />
        <template #content="{ close }">
            <div :class="['py-3 px-4', loading && 'pointer-events-none']">
                <AlertTriangleIcon class="text-red-500 w-14 h-14 mx-auto p-2" />
                <div v-if="!loading">
                    <slot name="content">
                        {{ content || 'Confirmez-vous la suppression ?' }}
                    </slot>
                    <div class="flex pt-3 mt-4 gap-3">
                        <Button variant="danger" full @click="$emit('confirm', close)">
                            {{ confirmText || 'Confirmer' }}
                        </Button>
                        <Button variant="success" full @click="close">Annuler</Button>
                    </div>
                </div>
                <p v-else class="text-center w-[23rem]">Processus est en cours.</p>
            </div>
        </template>
    </Popup>
</template>
