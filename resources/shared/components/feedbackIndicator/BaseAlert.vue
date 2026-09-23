<template>
    <div>
        <div
            :class="[
                'text-sm leading-6 flex-1 flex gap-2 items-center px-3 md:px-6 py-2 sm:px-3.5 w-full relative overflow-hidden',
                getTitle(alert).class,
            ]"
        >
            <component :is="getTitle(alert).icon" class="h-5 w-5" />
            <strong class="font-semibold flex-1">
                {{ getTitle(alert).heading }}
            </strong>

            <button v-if="type !== 'mobile'" type="button" class="-m-1.5 flex-none p-1.5" @click="alerts.dismiss(alert.id)">
                <span class="sr-only">Dismiss</span>
                <XIcon class="h-5 w-5" aria-hidden="true" />
            </button>
        </div>
        <p class="text-dark px-3 md:px-6 py-3 text-sm sm:px-3.5">
            {{ alert.title }}
        </p>
        <slot />
    </div>
</template>

<script setup lang="ts">
import { XIcon, InfoIcon, AlertDiamondIcon } from '@adersolutions/icons';
import { useAlert, IAlert } from '@shared/stores';

defineProps<{ alert: any; type?: 'dialog' | 'mobile' | 'default' }>();
const alerts = useAlert();

const getTitle = (alert: IAlert) => {
    switch (alert.type) {
        case 'success':
            return { heading: 'Réussie', icon: InfoIcon, class: 'bg-primary' };
        case 'error':
            return { heading: 'Avertissement!', icon: AlertDiamondIcon, class: 'bg-red-600' };
        default:
            return { heading: 'Info', icon: InfoIcon, class: 'bg-dark' };
    }
};
</script>
