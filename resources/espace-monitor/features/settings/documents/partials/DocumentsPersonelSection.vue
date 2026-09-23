<script setup lang="ts">
import { reactive } from 'vue';
import { PlusIcon } from '@adersolutions/icons';
import { Card, Button, DialogConfirm, FileLibrary } from '@shared/components';
import { useForm, router } from '@inertiajs/vue3';
import { useFileLibrary, useAlert } from '@shared/stores';
import { routes } from '@espace-monitor/routes';
import DocumentPersonelItem from './DocumentPersonelItem.vue';
import { fileTypeGroups } from '@shared/enums';
import { useFiles } from '@shared/hooks';
import type { StorageMediaType } from '@shared/types';

defineEmits(['close']);
type PropsType = {
    items: any[];
    keyName: 'media_diplom' | 'media_permis' | 'media_piece_identite';
};
const props = defineProps<PropsType>();

const fl = useFileLibrary();
const medias = useFiles();
const alert = useAlert();
const state = reactive({
    deleteMedia: null,
    showUploadTypeModal: false,
    uploading: false,
});
const form = useForm({
    [props.keyName]: props.items?.map((v) => ({ ...v.storage_media, media_id: v.id })) || [],
});

const onStoreFile = (value: StorageMediaType) => {
    if (form[props.keyName]?.some((item) => item.id === value.id)) {
        alert.show({
            title: 'Ce document a déjà été ajouté',
            type: 'error',
        });
    } else {
        form[props.keyName].push(value);
        state.showUploadTypeModal = false;
    }
};
const onDeleteMedia = (item) => {
    if (item.created_at) {
        state.deleteMedia = item;
    } else {
        onDeleteFile(item);
    }
};
const onDeleteFile = (item = state.deleteMedia) => {
    if (state.deleteMedia) {
        medias.delete(item.media_id).then(() => {
            form[props.keyName] = form[props.keyName].filter((media: StorageMediaType) => media.id !== item.id);
            router.reload();
            state.deleteMedia = null;
        });
    } else {
        form[props.keyName] = form[props.keyName].filter((media): StorageMediaType => media.id !== item.id);
    }
};

const onSubmit = () => {
    form.transform((data) => {
        data[props.keyName] = data[props.keyName].map((media) => media.storage_media_id || media.id);
        return data;
    });
    form.post(route(routes.settings.documentsPersonel.storeOrUpdate), {
        preserveScroll: true,
        onSuccess: () => {
            // onBack();
            // state.show = false;
        },
    });
};
</script>

<template>
    <div class="h-full flex flex-col flex-1">
        <Card as="section" class="h-full flex flex-col px-3 flex-1">
            <FileLibrary menu :types="fileTypeGroups.all" @submit="onStoreFile" />
            <article class="mt-3">
                <slot />
            </article>
            <Card class="flex-1" title="Documents telecharger" :action="{ icon: PlusIcon, link: true, onAction: () => fl.openMenu() }">
                <ul class="flex flex-col divide-y bg-white box overflow-clip mt-2">
                    <DocumentPersonelItem
                        v-for="(item, index) in form[keyName]"
                        :key="item.id"
                        :index="index"
                        :item="item"
                        @delete="onDeleteMedia(item)"
                    />
                </ul>
            </Card>

            <DialogConfirm
                :show="!!state.deleteMedia"
                :loading="medias.loading.deleting"
                button-attrs="dark"
                @close="state.deleteMedia = null"
                @confirm="onDeleteFile"
            >
                Etes-vous sûr que vous voulez supprimer ce document ?
            </DialogConfirm>
        </Card>
        <div class="page-actions">
            <Button variant="danger" full @click="onSubmit"> Enregistrer </Button>
        </div>
    </div>
</template>
