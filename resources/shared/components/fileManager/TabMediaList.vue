<script setup lang="ts">
import MediaItem from './MediaItem.vue';
import { useFileLibrary } from '@shared/stores';
import { Button, EmptyState, Errors, SearchField, Spinner, TabSwitch } from '@shared/components';
import { reactive } from 'vue';
import { fileTypeGroups } from '@shared/enums';

const emit = defineEmits(['search', 'change:tab', 'submit']);
const props = defineProps({
    multiple: Boolean,
    loading: Boolean,
    types: Array,
});
const fl = useFileLibrary();

const tabs = [
    { name: 'Tous', id: '' },
    { name: 'Images', id: fileTypeGroups.images.map((v) => v.split('/')[1]).join(',') },
    { name: 'Documents', id: fileTypeGroups.docs.map((v) => v.split('/')[1]).join(',') },
];
const state = reactive({
    tab: '',
    showTab: true,
});
const onSelectFile = (value) => {
    if (fl.selectedMedia.some((media) => media.id === value.id)) {
        fl.selectedMedia = fl.selectedMedia.filter((media) => media.id !== value.id);
    } else {
        if (fl.multiple || props.multiple) {
            fl.selectedMedia.push(value);
        } else {
            fl.selectedMedia = [value];
        }
    }
};
const onTabChange = (value) => {
    state.tab = value;
    emit('change:tab', {
        type: value ? value.split(',') : null,
    });
};
const onAddFile = () => {
    const data = fl.multiple ? fl.selectedMedia : fl.selectedMedia[0];
    fl.trigger(data);
    emit('submit', data);
    setTimeout(() => {
        fl.close();
    });
};
const emptyAction = [
    {
        label: 'Ajouter une image',
        variant: 'secondary',
        link: true,
        onAction: () => {
            fl.selectedTab = 0;
        },
    },
];
</script>

<template>
    <div class="flex flex-col relative flex-1">
        <div class="w-full flex-1">
            <div
                class="flex justify-between items-center gap-2 h-12 sticky top-0 z-1 backdrop-blur-lg rounded-t-xl bg-gray-100/70 p-2 max-md:shadow-down"
            >
                <TabSwitch v-if="state.showTab" :model-value="state.tab" :items="tabs" @change="onTabChange" />
                <SearchField v-else v-model="fl.search" class="h-8 flex-1" autofocus @change="$emit('search')" />
                <button class="btn btn-info btn-link" @click="state.showTab = !state.showTab">
                    {{ state.showTab ? 'Rechercher' : 'Filtre' }}
                </button>
            </div>
            <div v-if="Object.values(fl.errors)?.length" class="bg-red-100 m-2 px-1 pb-1 rounded-lg flex flex-col">
                <Errors v-if="fl.errors.media" :errors="fl.errors.media" />
                <Errors v-if="fl.errors.general" :errors="fl.errors.general" />
            </div>
            <div v-if="loading" class="flex-center h-full">
                <Spinner class="w-7 h-7" />
            </div>
            <ul
                v-else-if="fl.list.storageMedia.length"
                class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 md:gap-px md:p-2 rounded-t-xl"
            >
                <li v-if="fl.loading.upload" class="flex-center w-full h-full bg-gray-300 ring-1 ring-gray-200">
                    <Spinner class="w-7 h-7" />
                </li>
                <MediaItem
                    v-for="media in fl.list.storageMedia"
                    :key="media.id"
                    :types="types"
                    :media="media"
                    :selected="fl.selectedMedia.some((m) => m.id === media.id)"
                    @click="onSelectFile(media)"
                />
            </ul>
            <EmptyState v-else heading="Aucun média" class="py-10" :actions="emptyAction">
                L'extention accepter : jpg, jpeg, png et webp {{ fl.selectedTab }}
            </EmptyState>
        </div>
        <div class="sticky bottom-0 flex justify-end gap-2 p-2 backdrop-blur-lg bg-gray-100/70 shadow-up">
            <Button variant="secondary" @click="fl.close()">Fermer</Button>
            <Button variant="primary" class="!max-w-xs" full :disabled="!fl.selectedMedia.length" @click="onAddFile">
                Ajouter medias
            </Button>
        </div>
    </div>
</template>
