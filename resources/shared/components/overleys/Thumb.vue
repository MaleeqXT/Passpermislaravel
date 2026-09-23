<script setup lang="ts">
import { useAttrs } from 'vue';
import { ImageIcon } from '@adersolutions/icons';
import { SizeListType } from '@shared/types';
import { SizeEnum } from '@shared/enums';

// Define props interface
interface AvatarProps {
    src: string | (() => void);
    size?: SizeEnum;
    imageClass?: string;
    contain?: boolean;
    tooltip?: string;
}

// Define props using the interface
const props = defineProps<AvatarProps>();

// Default values
const attrs = useAttrs();
const SIZE: SizeListType = {
    '2xs': 'w-6 h-6',
    xs: 'w-8 h-8',
    sm: 'w-10 h-10',
    md: 'w-12 h-12',
    lg: 'w-16 h-16',
    xl: 'w-20 h-20',
    full: 'w-full h-full',
};

// Apply default values if not provided
const size = props.size || 'md';
const imageClass = props.imageClass || '';
</script>

<template>
    <div :class="['rounded-lg bg-gray-50 shadow-sm flex-center aspect-square overflow-hidden t-5', SIZE[size]]">
        <img
            v-if="typeof src === 'string'"
            v-bind="attrs"
            :class="['aspect-square t-5', SIZE[size], imageClass, contain ? 'object-contain' : 'object-cover']"
            :src="src"
            @error="(e) => ((e.target as HTMLImageElement).src = '/assets/images/placeholder.png')"
        />
        <component
            v-bind="attrs"
            :is="src || ImageIcon"
            v-else
            :class="['aspect-square w-4 opacity-70', imageClass, contain ? 'object-contain' : 'object-cover']"
        />
    </div>
</template>
