<script setup lang="ts">
import { Button } from '@shared/components';
import type { ButtonType } from '@shared/types';
type PropsType = {
    actions: ButtonType[];
    show: boolean;
    noMarging?: boolean;
};
defineProps<PropsType>();
</script>

<template>
    <div class="contents">
        <Teleport to="#layout">
            <div :class="['action-bar', show ? 'translate-y-0 ' : 'translate-y-40 invisible', noMarging && 'lg:!left-1/2']">
                <Button
                    v-for="(action, index) in actions"
                    :key="index"
                    v-bind="action"
                    :class="[action.label && 'md:px-7 px-5 ', 'h-10']"
                    @click="action.onAction?.()"
                >
                    {{ action.label }}
                </Button>
            </div>
        </Teleport>
    </div>
</template>
<style scoped>
.action-bar {
    @apply transition-all fixed md:max-w-xl z-50 bottom-0 md:bottom-5 inset-x-0 rounded-t-xl md:rounded-xl shadow-up flex gap-2 bg-dark/90 backdrop-blur-sm text-dark/80 p-2 lg:left-[calc(50%+9rem)] md:left-1/2 transform md:-translate-x-1/2;
}
</style>
