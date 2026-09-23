<script setup lang="ts">
import { GroupActionType } from '@shared/types';
import Button from './Button.vue';

// Define props with a type-safe structure
type PropsType = {
    actions: GroupActionType[];
    vertical?: boolean;
};
const props = withDefaults(defineProps<PropsType>(), {
    actions: () => [] as GroupActionType[],
});
const className = (action: GroupActionType) =>
    !props.vertical ? ['md:px-7 px-5', action.classItem, action.variant === 'primary' ? 'order-4' : 'order-1'] : ['p-2'];
</script>

<template>
    <div :class="['flex ', vertical ? 'flex-col gap-y-2' : 'gap-x-3 md:gap-x-4 justify-between md:justify-end items-center']">
        <!-- Loop through actions array -->

        <Button
            v-for="(action, index) in actions"
            :key="index"
            :class="className(action)"
            v-bind="action"
            class="md:px-7 px-5"
            @click="action.onAction"
        >
            {{ action.label || action.title }}
        </Button>
    </div>
</template>
