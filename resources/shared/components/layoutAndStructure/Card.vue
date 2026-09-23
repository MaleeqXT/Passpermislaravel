<template>
    <component
        :is="as"
        :class="[
            block && 'flex flex-col h-fit box ',
            gray && 'bg-gray-100',
            !gray && block && 'bg-white',
            block && mb,
            paddingClass(),
            separated && 'gap-y-2 md:gap-y-3',
        ]"
    >
        <div v-if="title || subtitle" class="w-full flex gap-3 items-start">
            <div class="flex-1">
                <h4 v-if="title" class="text-md font-semibold">
                    {{ title }}
                </h4>
                <p v-if="subtitle" class="text-sm text-gray-500">
                    {{ subtitle }}
                </p>
            </div>
            <slot name="action">
                <Button v-if="action" v-bind="action" @click="action.onAction?.()">
                    <template v-if="action.label" #default>
                        {{ action.label }}
                    </template>
                </Button>
            </slot>
        </div>
        <slot />
    </component>
</template>
<script setup lang="ts">
import { ButtonType } from '@shared/types';
import Button from '../actions/Button.vue';
type PropsType = {
    as?: string | object;
    padding?: 'none' | 'xs' | 'sm' | 'md' | 'lg' | boolean;
    separated?: boolean;
    gray?: boolean;
    mb?: string | boolean;
    block?: boolean;
    title?: string;
    action?: ButtonType;
    subtitle?: string;
};
const props = withDefaults(defineProps<PropsType>(), {
    as: 'div',
    padding: 'none',
    separated: true,
    gray: false,
    mb: 'mb-5',
    block: false,
});

const paddingClass = () => {
    switch (props.padding) {
        case 'none':
            return '';
        case 'xs':
            return 'p-1';
        case 'sm':
            return 'p-2';
        case 'md':
            return 'p-4';
        case 'lg':
            return 'p-6';
        default:
            return 'p-4';
    }
};
</script>
