<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue';
import { EmptyState } from '@shared/components';
import { Errors } from '../feedbackIndicator';
import { useAttrs } from 'vue';
import { computed } from 'vue';

const props = defineProps({
    types: Array,
});
const attrs = useAttrs();
const emit = defineEmits(['change']);

function preventDefaults(e) {
    e.preventDefault();
}

const events = ['dragenter', 'dragover', 'dragleave', 'drop'];
// make sure to import `ref` from Vue

// Create `active` state and manage it with functions
const active = ref(false);
const inputFile = ref(null);
const errors = ref([]);

const typesDisplay = computed(() => props.types.map((value) => value.split('/')?.[1] || '').join(', '));
function setActive() {
    active.value = true;
}
function setInactive() {
    active.value = false;
}

function onDrop(e) {
    setInactive(); // add this line too
    updatePhotoPreview(e.dataTransfer.files);
}

const updatePhotoPreview = (photos = inputFile.value.files) => {
    const dataTransfer = new DataTransfer();
    errors.value = [];

    for (const key in Array.from(photos)) {
        const photo = photos[key];
        if (props.types.some((value) => photo.type.includes(value))) {
            // const source = window.URL.createObjectURL(photo)
            // photo.src = source
            dataTransfer.items.add(photo);
        } else {
            errors.value = ["La taille a étè depasser 10Mo ou le fichier n'est pas valide"];
        }
    }
    emit('change', dataTransfer.files);
};

onMounted(() => {
    events.forEach((eventName) => {
        document.body.addEventListener(eventName, preventDefaults);
    });
});

onUnmounted(() => {
    events.forEach((eventName) => {
        document.body.removeEventListener(eventName, preventDefaults);
    });
});
</script>

<template>
    <div class="flex-center w-full h-full flex-col px-4" @drop.prevent="onDrop">
        <!-- add `data-active` and the event listeners -->
        <div
            :data-active="active"
            :class="[
                active ? 'bg-slate-100 border-slate-400' : '',
                'bg-white cursor-pointer hover:bg-slate-100 hover:border-slate-400 border-2 border-dashed border-gray-300 rounded-xl max-w-xl m-5 w-full',
            ]"
            @dragenter.prevent="setActive"
            @dragover.prevent="setActive"
            @dragleave.prevent="setInactive"
            @drop.prevent="onDrop"
            @click="inputFile?.click"
        >
            <input
                ref="inputFile"
                type="file"
                :multiple="true"
                class="hidden"
                v-bind="attrs"
                :accept="types"
                @change="updatePhotoPreview()"
            />

            <!-- share state with the scoped slot -->
            <EmptyState
                class="pt-2 pb-8"
                heading="Glisser-déposer"
                image="https://cdn.shopify.com/s/files/1/0262/4071/2726/files/emptystate-files.png"
                :actions="[{ label: 'Upload', name: 'upload', secondary: true }]"
            >
                Types d'images acceptés : {{ typesDisplay }}
            </EmptyState>
        </div>
        <Errors :errors="errors" />
    </div>
</template>
