<script setup lang="ts">
import { BookIcon, CheckIcon } from '@adersolutions/icons';
import { dateFormat, getName } from '@shared/utils';
defineProps({
    item: Object,
});
</script>
<template>
    <li :class="['btn-m w-full flex items-center box  p-2']">
        <div class="flex-1 text-sm">
            <b class="block">{{ dateFormat(item.created_at, 'letter') }} - avec {{ getName(item.reservation?.monitor?.user) }} </b>
            <p class="text-xs" v-html="item.comment"></p>
        </div>
    </li>
</template>
