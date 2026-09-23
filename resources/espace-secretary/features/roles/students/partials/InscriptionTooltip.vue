<template>
    <Popup :delay="200" hoverable>
        <slot />
        <template #content>
            <div class="flex flex-col gap-5 justify-between rounded-xl p-2">
                <h2 class="font-semibold text-lg text-center border-b pb-3">Les dates de connection</h2>
                <div class="flex flex-col gap-3">
                    <div class="flex justify-between gap-2 text- white px-2">
                        <span class="">Date d’inscription :</span>
                        <span class="font-semibold">{{ inscriptionDate }}</span>
                    </div>
                    <!-- <div class="flex justify-between gap-2 px-2">
                        <span>Date derniere heure :</span>
                        <span class="font-semibold">{{ lastHourDate ?? '_' }}</span>
                    </div> -->
                </div>
            </div>
        </template>
    </Popup>
</template>

<script setup lang="ts">
import { Popup } from '@shared/components';

defineProps({
    inscriptionDate: String,
    lastHourDate: String,
});
</script>
