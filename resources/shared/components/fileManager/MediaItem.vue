<script setup lang="ts">
import { computed } from 'vue';
import { CheckCircleIcon, NoteIcon } from '@adersolutions/icons';
import { fileTypeGroups } from '@shared/enums';

const props = defineProps({
    media: {
        type: Object,
        default: () => ({}),
    },
    types: {
        type: Array,
        default: () => [],
    },
    selected: Boolean,
});
const parsedTypes = computed(() => props.types.map((type) => type.split('/')[1]));
</script>

<template>
    <li
        :class="[
            '!p-0 !mb-0 cursor-pointer relative t-2  w-full flex aspect-square h-fit bg-white',
            selected ? 'border-4 border-dark' : '',
            !parsedTypes.includes(media.type) ? 'opacity-35 pointer-events-none saturate-50' : '',
        ]"
    >
        <b v-if="selected" class="absolute right-0.5 top-0.5 bg-dark text-white p-0.5 rounded-full flex-center pr-2 text-2xs">
            <CheckCircleIcon class="h-5 w-5" /> Select
        </b>
        <div
            v-if="fileTypeGroups.docs.some((t) => t.includes(media?.type))"
            class="aspect-square object-cover rounded-xl w-full h-full flex-center flex-col"
        >
            <NoteIcon class="w-6 h-6 text-slate-600" />
            {{ media.type }}
        </div>
        <img
            v-else
            :src="media.path"
            class="aspect-square object-cover size-full"
            alt=""
            @error="(e) => (e.target.src = '/assets/images/placeholder.png')"
        />
    </li>
</template>
