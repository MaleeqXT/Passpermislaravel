<script setup lang="ts">
import { NoteIcon } from '@adersolutions/icons';
import { Button, Thumb } from '@shared/components';
import { fileTypeGroups } from '@shared/enums';

defineEmits(['delete']);
defineProps({
    item: Object,
    deleting: Boolean,
});
</script>

<template>
    <div class="flex items-center gap-5 bg-slate-50 box overflow-clip p-1">
        <div class="flex-1 flex gap-3 items-center">
            <div v-if="fileTypeGroups.docs.includes(item.type)" class="flex-center">
                <NoteIcon class="w-8 h-8 text-slate-600 rounded-lg bg-slate-200 shadow-sm p-1" />
            </div>
            <Thumb v-else :src="item.thumb || item.path" size="xs" class="bg-white" />
            <div class="flex-1">
                <p class="font-bold truncate max-w-md">{{ item.name }}</p>
            </div>
        </div>
        <div class="flex-center gap-3">
            <a :href="item.path" target="_blank" class="btn btn-secondary" rel="noopener noreferrer"> Voir </a>
            <Button variant="danger" :loading="deleting" @click="$emit('delete', item.id)">
                {{ item.created_at ? 'Supprimer' : 'Annuler' }}
            </Button>
        </div>
    </div>
</template>
