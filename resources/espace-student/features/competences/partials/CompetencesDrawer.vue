<script setup lang="ts">
import { Button, Drawer } from '@shared/components';
import CompetenceItem from './CompetenceItem.vue';
import { LineProgress } from '@common/components';

defineEmits(['close']);

defineProps({
    item: [Object, null],
});
</script>

<template>
    <Drawer :show="!!item" title="Compétences" @close="$emit('close')">
        <div class="flex flex-col p-4 gap-4 bg-dark-block mx-3">
            <div class="flex justify-between items-center gap-3">
                <p class="text-base font-medium flex gap-2">
                    <span class="w-7 h-7 p-1 bg-dark/10 rounded-lg flex-center">
                        {{ item?.position }}
                    </span>
                    {{ item?.name }}
                </p>
            </div>
            <LineProgress :done="item?.competencies_done_count || 0" :total="item?.competencies_count || 0" />
            <p class="text-xs">{{ item?.label }}</p>
        </div>
        <ul class="mt-5 px-3 divide-y">
            <CompetenceItem v-for="v in item?.competencies" :key="v?.id" :item="v" />
        </ul>
    </Drawer>
</template>
