<script setup lang="ts">
import moment from 'moment-timezone';
import { onMounted, ref } from 'vue';

const props = defineProps({
    title: String,
    subtitle: String,
    disableTime: Boolean,
});
const time = ref();
onMounted(() => {
    if (props.disableTime) {
        return;
    }
    time.value = moment().format('HH:mm');
    setInterval(() => {
        time.value = moment().format('HH:mm');
    }, 1000 * 60);
});
</script>
<template>
    <dl class="flex pb-6 pt-3 drop-shadow">
        <dd class="flex-1">
            <h1 class="text-2xl md:text-3xl font-bold leading-6 capitalize mb-1 flex gap-2">
                {{ title }}
            </h1>
            <p class="opacity-75 text-md md:text-lg">
                {{ subtitle }}
            </p>
        </dd>
        <dd class="flex-center gap-2 bg-slate-900 h-fit rounded-full p-0.5 md:p-1">
            <span v-if="time" class="flex-center text-white px-2 text-sm font-bold mt-px w-fit">
                {{ time }}
            </span>
            <slot />
        </dd>
    </dl>
</template>
