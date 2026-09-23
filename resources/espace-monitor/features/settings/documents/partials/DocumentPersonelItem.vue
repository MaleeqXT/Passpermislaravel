<script setup lang="ts">
import { AttachmentIcon, DeleteIcon } from '@adersolutions/icons';
import { Button, Thumb } from '@shared/components';
import { fileTypeGroups, SizeEnum } from '@shared/enums';

defineEmits(['delete']);
defineProps({
    item: Object,
    index: Number,
    subtitle: String,
    deleting: Boolean,
});
</script>

<template>
    <li class="flex items-center md:hover:bg-slate-100 flex-1 gap-3 p-1 btn-m">
        <div v-if="fileTypeGroups.docs.includes(item.type)" class="flex items-center">
            <AttachmentIcon class="w-12 h-12 text-slate-600 rounded-lg bg-slate-100 border shadow-sm p-2.5" />
        </div>
        <Thumb v-else :src="item.path" :size="SizeEnum.SM" />
        <p class="font-bold line-clamp-1 flex-1 text-sm">{{ item.name }}</p>
        <Button link variant="danger" :icon="DeleteIcon" @click="$emit('delete', item.id)" />
    </li>
</template>
