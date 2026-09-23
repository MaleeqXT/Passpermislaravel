<script setup lang="ts">
import { useFileLibrary } from '@shared/stores';
import { Badge, Button, EmptyState } from '@shared/components';
import { computed } from 'vue';
import { NoteIcon } from '@adersolutions/icons';
import { fileTypeGroups } from '@shared/enums';
import { useFiles } from '@shared/hooks';

const fm = useFileLibrary();
const file = useFiles();
const media = computed<any>(() => {
    const val = fm.selectedMedia;
    if (val.length) {
        return val[val.length - 1];
    }
    return;
});

const onDelete = () => {
    if (!media.value) return;
    file.delete(media.value.id).then((data) => {
        if (data?.message) {
            fm.list.storageMedia = fm.list.storageMedia.filter((m) => m.id !== media.value?.id);
            fm.selectedMedia = fm.selectedMedia.filter((m) => m.id !== media.value?.id);
        }
    });
};
</script>

<template>
    <div class="w-full bg-gray-200 hidden md:flex flex-col">
        <h2 class="mb-2 font-semibold">Fichier sélectionné</h2>
        <div v-if="media" class="flex flex-col gap-2 aspect-square">
            <a
                v-if="fileTypeGroups.docs.some((t) => t.includes(media?.type))"
                :href="media.path"
                target="_blank"
                class="aspect-square object-cover rounded-lg w-full h-full flex-center flex-col"
            >
                <NoteIcon class="w-6 h-6 text-slate-600" />
                {{ media.type }}
            </a>
            <a v-else :href="media.path" class="bg-gray-100 h-full flex-center rounded-xl overflow-clip" target="_blank">
                <img
                    :src="media.path"
                    class="w-full aspect-square object-contain"
                    @error="(e) => { const target = e.target as HTMLImageElement; if (target) target.src = '/assets/images/placeholder.png'; }"
                />
            </a>
            <h3 class="text-md font-bold break-words">
                {{ media.name }}
            </h3>
            <div class="flex gap-2">
                <Badge :value="{ class: 'dark', name: media.type }" />
                <Badge :value="{ class: 'info', name: media.is_active ? 'Actif' : 'Brouillon' }" />
            </div>
        </div>
        <div v-else class="bg-gray-100 h-full flex-center rounded-xl aspect-square">
            <EmptyState heading="Aucun fichier sélectionné">
                <p class="text-center px-10">Veuillez sélectionner un fichier pour voir les détails.</p>
            </EmptyState>
        </div>
        <div class="mt-2">
            <Button variant="danger" :disabled="!media" :loading="file.loading.deleting" full @click="onDelete">Supprimer</Button>
        </div>
    </div>
</template>
