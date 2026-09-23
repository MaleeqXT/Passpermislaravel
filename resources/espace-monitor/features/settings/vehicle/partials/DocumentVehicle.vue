<script setup lang="ts">
import { PlusIcon } from '@adersolutions/icons';
import { Card, FileLibrary } from '@shared/components';
import { useFileLibrary } from '@shared/stores';
import { toRef } from 'vue';
import MediaUploadedItem from './MediaUploadedItem.vue';
import { reactive } from 'vue';
import { fileTypeGroups } from '@shared/enums';

defineEmits(['close']);
const props = defineProps({
    values: {
        type: Object,
        default: () => ({}),
    },
});

const fl = useFileLibrary();
const form = toRef(props, 'values');
const state = reactive({
    assurance: false,
});
const onFileOpen = (assurance = false) => {
    state.assurance = assurance;
    fl.open();
};
const onStoreFile = (value: any) => {
    if (state.assurance) {
        form.value.media_assurance.push({ ...value, storage_media: value });
    } else {
        form.value.media_carte.push({ ...value, storage_media: value });
    }
};
const onDeleteFile = (id: string, assurance = false) => {
    if (assurance) {
        form.value.media_assurance = form.value.media_assurance.filter((item) => item.id !== id);
    } else {
        form.value.media_carte = form.value.media_carte.filter((item) => item.id !== id);
    }
};
</script>
<template>
    <div class="flex flex-col space-y-4 mt-5 border-t">
        <FileLibrary menu :types="fileTypeGroups.all" @submit="onStoreFile" />
        <Card
            padding="none"
            title="Carte grise"
            subtitle="Merci de fournir le recto & verso de ce document"
            :action="{ icon: PlusIcon, link: true, onAction: () => onFileOpen() }"
        >
            <ul class="divide-y">
                <MediaUploadedItem
                    v-for="(media, index) in form.media_carte"
                    :id="media.id"
                    :key="media.id"
                    :errors="form.errors['media_carte.' + index]"
                    :files="media.storage_media"
                    @delete="onDeleteFile(media.id)"
                />
            </ul>
        </Card>
        <Card
            padding="none"
            title="Assurance auto / carte verte"
            subtitle="Merci de fournir une assaurance en cours de validité"
            :action="{ icon: PlusIcon, link: true, onAction: () => onFileOpen(true) }"
        >
            <ul class="divide-y">
                <MediaUploadedItem
                    v-for="(media, index) in form.media_assurance"
                    :id="media.id"
                    :key="media.id"
                    :errors="form.errors['media_assurance.' + index]"
                    :files="media.storage_media"
                    @delete="onDeleteFile(media.id, true)"
                />
            </ul>
        </Card>
    </div>
</template>
