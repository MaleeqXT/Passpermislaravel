<template>
    <span
        v-show="item.name"
        :class="['text-2xs rounded-md px-3 py-0.5 h-fit font-medium', 'status-' + (item.class || variant || 'default')]"
        :tooltip="(short ? item.name : undefined) || item.desc"
    >
        <slot>
            <component :is="item.icon || icon" v-if="item.icon || icon" class="w-3.5 h-3.5" />
            {{ short ? item.sn || getInitials(item.name) : item.name }}
        </slot>
    </span>
</template>

<script setup lang="ts">
import { computed, PropType } from 'vue';
import { getInitials } from '@shared/utils';
import { GlobalObjType } from '@common/types';
type PropsType = {
    id?: number | string | boolean;
    options?: Record<string, any>;
    variant?: GlobalObjType<string>['class'];
    value?: Record<string, any>;
    short?: boolean;
    icon?: Function;
};
const props = defineProps<PropsType>();

const item = computed(() => {
    if (props.value) return props.value;
    const list = Object.values(props.options || {}).find((item) => item.id == props.id);
    const idKey = props.id !== undefined ? String(props.id) : '?';
    return (props.options || {})?.[idKey] || list || {};
});
</script>
