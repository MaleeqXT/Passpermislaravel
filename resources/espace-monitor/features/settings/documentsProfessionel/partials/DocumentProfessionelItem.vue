<script setup lang="ts">
import { DeleteIcon, NoteIcon } from '@adersolutions/icons';
import { Button, Thumb } from '@shared/components';
import { fileTypeGroups } from '@shared/enums';

defineEmits(['delete']);
defineProps({
    item: Object,
    index: Number,
});
</script>

<template>
    <li class="flex items-center md:hover:bg-slate-100">
        <div class="flex-1 flex gap-3 p-1 items-center btn-m">
            <div v-if="fileTypeGroups.docs.includes(item.type)" class="flex items-center">
                <NoteIcon class="w-12 h-12 text-slate-600 rounded-lg bg-slate-100 border shadow-sm p-2.5" />
            </div>
            <Thumb v-else :src="item.path" size="sm" />
            <div class="text-sm flex-1">
                <p class="font-bold">{{ item.name }}</p>
                <span class="opacity-75 text-sm">Autorisation d'enseigner {{ index + 1 }}</span>
            </div>
            <Button link variant="danger" class="text-xs btn-m" :icon="DeleteIcon" @click="$emit('delete', item.id)"></Button>
        </div>
    </li>
</template>
