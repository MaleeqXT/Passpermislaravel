<script setup lang="ts">
import { reactive } from 'vue';
import { ChevronRightIcon, ClipboardCheckIcon, LocationIcon } from '@adersolutions/icons';
import { Card, Button, Dialog } from '@shared/components';
import { copyText } from '@shared/utils';
const props = defineProps({
    lieu: {
        type: Object,
        default: () => ({}),
    },
    title: String,
});
const state = reactive({
    showModal: false,
});
const handleCopy = () => {
    copyText(`${props?.lieu?.name}, ${props?.lieu?.zone?.name}`);
};
</script>

<template>
    <div class="contents">
        <h4 v-if="title" class="font-semibold text-gray-500 text-sm mb-1">{{ title }}</h4>
        <h5 class="font-semibold text-md mb-1">Point de rendez-vous</h5>

        <div class="bg-white box mb-5 overflow-clip">
            <img src="/assets/images/map-location.png" alt="map-location" />
            <div class="h-rainbow"></div>
            <div class="flex justify-between items-center py-2 px-3 overflow-clip relative" @click="state.showModal = true">
                <div class="rainbow absolute -top-px"></div>
                <p class="text-xs font-medium">{{ lieu?.zone?.name }}, {{ lieu?.name }}</p>
                <ChevronRightIcon class="w-5 opacity-50" />
            </div>
        </div>
        <Dialog
            :show="state.showModal"
            class-name="!p-0"
            custom-class="modal-mobile max-h-[calc(100%-3.5rem)] md:max-h-[calc(100vh-3rem)] !h-fit"
            title="Rejoindre le lieu convenu"
            z-index="z-[902]"
            @close="state.showModal = false"
        >
            <Card class="relative">
                <img
                    src="/assets/images/map-location-large.png"
                    class="absolute inset-0 z-0 size-full object-cover"
                    alt="map-location-large"
                />
                <div
                    class="relative h-28 bg-gradient-to-b from-white/10 via-white/80 to-white flex flex-col justify-end items-center p-2 z-10"
                >
                    <span class="font-bold">Site de rendez-vous</span>
                    <p class="text-opacity-70">{{ lieu?.zone?.name }}, {{ lieu?.name }}</p>
                </div>
                <div class="rainbow"></div>
            </Card>
            <Card class="bg-white flex flex-col gap-y-2 py-8 relative" as="ul" padding>
                <Button variant="dark" full :href="lieu?.url" :disabled="!lieu?.url" self>
                    Ouvrir dans google maps
                    <LocationIcon class="w-4 ml-2" />
                </Button>

                <Button outline variant="secondary" full @click="handleCopy">
                    Copy l'adresse
                    <ClipboardCheckIcon class="w-4" />
                </Button>
            </Card>
        </Dialog>
    </div>
</template>
