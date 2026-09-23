<script setup lang="ts">
import type { ReservationType } from '@common/types';
import { reactive, watch } from 'vue';
import { dateFormat } from '@shared/utils';
import { Spinner } from '@shared/components';

type PropsType = {
    item: ReservationType;
    icon: any;
    hint: string;
    title: string;
    className?: string;
};
const props = defineProps<PropsType>();

// ---------------- RESERVATION COMMENTS ----------------
const stateLocal = reactive({
    reservationComments: [] as any[],
    loadingComments: false,
});

const fetchReservationComments = async () => {
    if (!props.item?.id) return;

    stateLocal.loadingComments = true;
    try {
        const response = await fetch(`/reservations/${props.item.id}/comments`);
        const data = await response.json();
        stateLocal.reservationComments = data.data || [];
    } catch (error) {
        console.error('Error fetching comments:', error);
        stateLocal.reservationComments = [];
    } finally {
        stateLocal.loadingComments = false;
    }
};

watch(() => props.item?.id, (newId: string | undefined) => {
    if (newId) {
        fetchReservationComments();
    } else {
        stateLocal.reservationComments = [];
    }
}, { immediate: true });
</script>
<template>
    <div class="bg-gradient-to-tr ring flex-1 flex md:flex-col gap-x-1 gap-y-1 p-1 text-sm rounded-lg">
        <div
            :class="['flex-center flex-col bg-white w-14 h-full md:h-8 md:w-full rounded-md text-2xs', className || 'text-dark']"
            :style="{ backgroundColor: props.item.training ? props.item.color || props.item.training.offer?.color : '' }"
        >
            <component :is="icon" class="w-5" />
            <p>
                {{ hint }}
            </p>
        </div>
        <div class="flex-1 relative flex flex-col z-1">
            <p class="line-clamp-2 text-wrap font-semibold">{{ title }}</p>
            <p class="font-light flex text-2xs gap-x-1 md:flex-col gap-y-1 w-fit">
                <span class="bg-white text-dark px-1 py-0.5 flex-1 rounded-md border border-slate-200 line-clamp-1 w-fit">
                    {{ props.item.lieu?.name }}
                </span>
                <span class="bg-white text-dark px-1 py-0.5 rounded-md border border-slate-200 line-clamp-1 w-fit">
                    {{ props.item.start_at }} à {{ props.item.end_at }}
                </span>
                <template v-if="stateLocal.reservationComments.length > 0">
                    <span
                        v-for="(comment, index) in stateLocal.reservationComments"
                        :key="comment.id"
                        class="bg-gray-50 text-gray-600 px-1 py-0.5 rounded-md border border-slate-200 line-clamp-2 w-fit text-[8px] mt-1"
                    >
                 {{ comment.comment }}
                    </span>
                </template>
                <span v-if="stateLocal.loadingComments" class="text-center py-1 flex items-center gap-1">
                    <Spinner class="w-4 h-4" />
                    <span class="text-[8px] text-gray-500">Chargement...</span>
                </span>
            </p>
        </div>
    </div>
</template>
