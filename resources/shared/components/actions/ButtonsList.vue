<template>
    <Popup class="inline-block text-left" position="bottom-end">
        <slot>
            <MenuHorizontalIcon class="w-8 h-8 p-1.5 cursor-pointer active:bg-slate-100 hover:text-primary rounded-full" />
        </slot>
        <template #content>
            <ul class="flex flex-col divide-y">
                <li v-for="(action, idx) in actions.filter(Boolean)" :key="idx">
                    <Button v-bind="action" :class="[action.href && '!px-3 !justify-between']" full>
                        <span class="flex-1 px-2">{{ action.label }}</span>
                        <ArrowRightIcon v-if="action.href" class="w-5 h-5" />
                    </Button>
                </li>
            </ul>
        </template>
    </Popup>
</template>


<script setup lang="ts">
import Button from './Button.vue';
import { ArrowRightIcon, MenuHorizontalIcon } from '@adersolutions/icons';
import Popup from '../overleys/Popup.vue';
import type { ButtonType } from '@shared/types';

interface ButtonListProps {
    actions: ButtonType[];
}
defineProps<ButtonListProps>();
</script>
