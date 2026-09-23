<script setup lang="ts">
import { ChevronLeftIcon, PhoneIcon } from '@adersolutions/icons';
import { routes } from '@espace-monitor/routes';
import { Button } from '@shared/components';
defineEmits(['close', 'click']);
defineProps({
    phone: String,
    href: String,
    closed: Boolean,
    disabled: Boolean,
    loading: Boolean,
    apply: Boolean,
    submit: Boolean,
    text: String,
    back: Boolean,
    icon: Function,
});
const onBack = () => {
    history.back();
};
</script>
<template>
    <div
        class="gap-2 bg-white fixed max-md:left-0 md:sticky bottom-0 shadow-box-2 h-fit z-1 md:max-w-xl w-full md:bottom-5 md:rounded-full md:border md:mx-auto"
    >
        <div class="flex items-center gap-4 p-2 text-xs">
            <Button
                class="px-4"
                link
                dark
                :href="closed || back ? '' : href || route(routes.dashboard.index)"
                :icon="ChevronLeftIcon"
                @click="back ? onBack() : $emit('close')"
            >
                Retour
            </Button>
            <Button
                :href="apply ? '' : `tel:${phone}`"
                :icon="phone ? PhoneIcon : icon || null"
                fullwidth
                yellow
                external
                :disabled="disabled"
                :submit="submit"
                :loading="loading"
                @click="$emit('click')"
            >
                {{ text || 'Appel' }}
            </Button>
        </div>
    </div>
</template>
