<script setup lang="ts">
import { CheckIcon, NoteIcon } from '@adersolutions/icons';
import { Badge, Button, Errors } from '@shared/components';
import { fileTypeGroups } from '@shared/enums';
import { useFiles } from '@shared/hooks';
import { StorageMediaType } from '@shared/types';
const emit = defineEmits(['close', 'delete']);

defineProps({
    media: {
        type: Object,
        default: () => ({}),
    },
    id: String,
    errors: [String, Array],
});
const files = useFiles();
const onDeleteFile = (media: StorageMediaType, id = null) => {
    if (id && media.created_at) {
        files.delete(id).then(() => {
            emit('delete');
        });
    } else {
        emit('delete');
    }
};
</script>
<template>
    <li class="py-1">
        <div class="flex items-center gap-3">
            <a :href="media.path" class="flex items-center gap-3 flex-1" download>
                <div v-if="fileTypeGroups.docs.includes(media.type)" class="flex items-center">
                    <NoteIcon class="w-12 h-12 text-slate-600 rounded-lg bg-slate-100 border shadow-sm p-2.5" />
                </div>
                <img v-else :src="media.path" class="w-12 h-12 rounded-lg border bg-slate-100 shadow-sm" />
                <div class="">
                    <span class="block mb-1 text-slate-700">{{ media.name }}</span>
                    <div class="flex items-center gap-2">
                        <Badge v-if="media.created_at" success class="text-xxs !py-0 !h-5" :icon="CheckIcon"> Téléchargé </Badge>
                        <span class="text-blue-500 text-xs underline">Voir le document</span>
                    </div>
                </div>
            </a>
            <Button link danger @click="onDeleteFile(media, id)">
                <span class="text-xs">{{ media.created_at ? 'Supprimer' : 'Annuler' }}</span>
            </Button>
        </div>
        <Errors :errors="errors" />
    </li>
</template>
