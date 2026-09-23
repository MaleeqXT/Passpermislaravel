<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { Card, EmptyState, Errors } from '@shared/components';
import { RefreshIcon, NoteIcon, ViewIcon, ImageIcon, DeleteIcon, PlusIcon } from '@adersolutions/icons';
import { useFileLibrary } from '@shared/stores';
import { fileTypeGroups } from '@shared/enums';
const emit = defineEmits(['delete', 'change']);
const props = defineProps({
    error: String,
    src: String,
    mediaType: String,
    mediaSource: String,
    title: String,
    menu: Boolean,
    inline: Boolean,
});

const FileLibrary = useFileLibrary();
const mediaSource = ref(props.mediaSource || Math.random().toString(36).substring(7));
const onChangeMedia = () => {
    FileLibrary.types = props.mediaType === 'pdf' ? fileTypeGroups.docs : null;
    if (props.menu) {
        FileLibrary.openMenu(mediaSource.value);
    } else {
        FileLibrary.open(mediaSource.value);
    }
};

onMounted(() => {
    FileLibrary.submit(({ data, source }) => {
        if (source === mediaSource.value) {
            emit('change', data);
        }
    });
});
</script>

<template>
    <div>
        <div v-if="inline && !src" class="flex gap-2 items-center w-full box bg-white p-1">
            <ImageIcon class="w-12 h-12 text-slate-600 p-4 box !rounded-lg bg-dark/10" />
            <p class="flex-1">Vide pour l'instant</p>
            <button class="btn btn-info" @click="onChangeMedia">
                <PlusIcon class="w-5 h-5" />
                Ajouter
            </button>
        </div>
        <EmptyState
            v-else-if="!src"
            heading="Vide pour l'instant"
            :image="ImageIcon"
            class="py-5 group bg-white box aspect-square flex-center flex-col"
            @click.prevent="onChangeMedia"
        >
            <button class="group-hover:underline group-hover:text-blue-400 px-3 mt-3" type="button">
                Appuyez ici pour ajouter une photo.
            </button>
        </EmptyState>

        <div v-else :class="['relative group overflow-hidden !mb-0 box bg-white', inline ? 'flex items-center gap-2' : '']" gray block>
            <div
                v-if="mediaType === 'pdf'"
                :class="[
                    'flex-center flex-col w-full aspect-square h-full  ',
                    inline ? 'max-w-16 p-3 text-2xs bg-gray-100' : 'bg-whiteshadow-sm',
                ]"
            >
                <NoteIcon class="w-10 h-10 text-slate-600" />
                {{ mediaType }}
            </div>
            <img v-else :src="src" :class="[inline ? 'w-16 h-16' : 'w-full aspect-square ', 'object-contain bg-white shadow-sm']" />
            <p v-if="inline" class="flex-1">{{ title }}</p>
            <div
                :class="[
                    inline
                        ? 'flex mr-2 rounded-lg overflow-clip'
                        : 'absolute inset-x-0 bottom-0 flex-center opacity-0 group-hover:opacity-100 t-3 drop-shadow-2xl bg-dark/20',
                ]"
            >
                <a target="_blank" :href="src" tooltip="Voir" class="flex-1">
                    <ViewIcon class="w-full h-8 p-2 btn-m text-white bg-dark shadow-2xl shadow-white" />
                </a>
                <div tooltip="Changer" class="flex-1">
                    <RefreshIcon class="w-full h-8 p-2 btn-m text-white bg-blue-500 shadow-2xl" @click="onChangeMedia" />
                </div>
                <div tooltip="Supprimer" class="flex-1">
                    <DeleteIcon class="w-full h-8 p-2 btn-m text-white bg-red-500 shadow-2xl" @click="$emit('delete')" />
                </div>
            </div>
        </div>
        <Errors v-if="error" :errors="error" />
    </div>
</template>
