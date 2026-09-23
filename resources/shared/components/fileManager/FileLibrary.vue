<script setup lang="ts">
import { onMounted, ref, PropType } from 'vue';
import { ImportIcon, ImageIcon } from '@adersolutions/icons';
import { Button, Dialog } from '..';
import { useFileLibrary } from '@shared/stores';
import { TabGroup, TabList, Tab, TabPanels, TabPanel } from '@headlessui/vue';
import { useFiles } from '../../hooks/useFiles';
import TabDropZone from './TabDropZone.vue';
import TabMediaList from './TabMediaList.vue';
import TabInsertUrl from './TabInsertUrl.vue';
import { FileType, fileTypeGroups } from '@shared/enums';
import Drawer from '../layoutAndStructure/Drawer.vue';
import BlockPreview from './BlockPreview.vue';
import { ActionSheet } from '@common/components';

const props = defineProps({
    multiple: Boolean,
    menu: Boolean,
    types: {
        type: Array as PropType<FileType[]>,
        default: () => fileTypeGroups.images,
    },
});
const photoShotTriggerRef = ref(null);

const emit = defineEmits(['close', 'submit']);
const fl = useFileLibrary();
const medias = useFiles();
const tabs = [
    {
        name: 'Telecharger photos',
        icon: ImportIcon,
    },
    {
        name: 'Medias disponibles',
        icon: ImageIcon,
    },
];

const onFileUpload = (value: any[]) => {
    fl.selectedTab = 1;
    // const formData = new FormData();
    fl.loading.upload = true;
    medias
        .upload(value, value?.length > 1)
        .then(fl.fetch)
        .catch((err) => {
            if (err?.response?.data?.errors) {
                fl.errors = err?.response?.data?.errors || {};
            } else {
                fl.errors.general = err?.response?.data?.message || null;
            }
        })
        .finally(() => {
            fl.loading.upload = false;
        });
};

// const fl.fetch = (loadmore = false, filters: any = {}) => {
//     const params = { name: fl.search, ...filters };
//     if ((loadmore && !fl.list.links?.next) || fetchingMedia.value) {
//         return;
//     }
//     if (loadmore) {
//         params.page = fl.list.meta?.current_page + 1;
//     }
//     fetchingMedia.value = true;
//     axios
//         .get(route(fileapi.medias), { params })
//         .then(({ data }) => {
//             if (data?.storageMedia) {
//                 if (loadmore) {
//                     fl.list.storageMedia = [...fl.list.storageMedia, ...data.storageMedia];
//                     fl.list.links = data.links;
//                     fl.list.meta = data.meta;
//                 } else {
//                     fl.list = data;
//                 }
//             }
//         })
//         .finally(() => {
//             fetchingMedia.value = false;
//         });
// };
const onTakePhoto = (e) => {
    // fl.open()
    fl.loading.takingPhoto = true;
    medias
        .upload(e.target.files, fl.multiple)
        .then(({ storageMedia }) => {
            fl.trigger(storageMedia);
            emit('submit', storageMedia);
            fl.close();
        })
        .finally(() => {
            fl.loading.takingPhoto = false;
            fl.settings.showTypeUpload = false;
        });
};

onMounted(() => {
    fl.multiple = !!props.multiple;
});
</script>

<template>
    <div class="contents">
        <Drawer :show="fl.show" width="full" pos="bottom" @close="fl.close()" @scroll:end="fl.fetch(true)">
            <TabGroup
                class="flex max-md:flex-col flex-1 h-full"
                as="div"
                :selected-index="fl.selectedTab"
                @change="fl.selectedTab = $event"
            >
                <TabList class="flex flex-col md:w-80 bg-gray-200 p-3 gap-2 md:max-h-[calc(100svh-56px)] md:sticky top-0">
                    <h2 class="text-dark font-bold text-lg md:my-3 pb-3 max-md:text-center max-md:border-b border-gray-300">
                        Gestion des fichier
                    </h2>
                    <div class="flex md:flex-col gap-1 flex-1 max-md:sticky top-14">
                        <Tab v-for="tab in tabs" :key="tab.name" v-slot="{ selected }" class="focus:outline-none max-md:flex-1">
                            <p
                                :class="[
                                    'flex items-center justify-start gap-x-2 rounded-md py-2 md:h-10 text-sm leading-6 cursor-pointer active:opacity-75 hover:bg-white/10',
                                    selected ? 'btn-header font-bold' : 'px-1',
                                ]"
                            >
                                <component :is="tab.icon" class="w-5" />
                                <span class="font-medium"> {{ tab.name }}</span>
                            </p>
                        </Tab>
                    </div>
                    <BlockPreview />
                </TabList>
                <TabPanels class="flex-1 bg-gray-100 max-md:rounded-t-xl">
                    <TabPanel as="template">
                        <TabDropZone :types="fl.types || types" @change="onFileUpload" />
                    </TabPanel>
                    <TabPanel class="relative flex h-full">
                        <TabMediaList
                            :loading="fl.fetching"
                            :types="fl.types || types"
                            @search="fl.fetch"
                            @change:tab="fl.fetch(false, $event)"
                            @submit="$emit('submit', $event)"
                        />
                    </TabPanel>
                    <TabPanel as="template">
                        <TabInsertUrl />
                    </TabPanel>
                </TabPanels>
            </TabGroup>
        </Drawer>

        <ActionSheet
            v-if="menu"
            :show="fl.settings.showTypeUpload"
            :actions="[
                { label: 'Ajouter un document', onAction: () => fl.open(fl.source) },
                { label: 'Prendre une photo', onAction: () => photoShotTriggerRef?.click?.() },
            ]"
            @close="fl.settings.showTypeUpload = false"
        >
            <input ref="photoShotTriggerRef" type="file" class="hidden" accept="image/*;capture=camera" @change="onTakePhoto" />
        </ActionSheet>
    </div>
</template>
