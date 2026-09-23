<script setup lang="ts">
import { AffiliateIcon, CalendarCheckIcon, StatusActiveIcon } from '@adersolutions/icons';
import { CancelStatus } from '@common/enums';
import { useMonitorSpace } from '@espace-monitor/stores';
import { isOutdated } from '@shared/utils';
import { computed } from 'vue';
import ItemCard from './ItemCard.vue';
import type { ProposalType, ReservationType } from '@common/types';

type PropsType = {
    item: ReservationType;
};
defineEmits(['select', 'select:proposal']);
const props = defineProps<PropsType>();
const { state, proposals } = useMonitorSpace();
const isActive = computed(() => proposals.data?.find((v) => v.reservation_id === props.item.id));
const onAddProposal = () => {
    if (isActive.value) {
        proposals.data = proposals.data.filter((v) => v.reservation_id !== props.item.id);
    } else {
        const payload: Partial<ProposalType> = {
            reservation_id: props.item.id,
            reservation: props.item,
            student_id: state.student?.id,
            comment: '',
        };
        proposals.data = [...proposals.data, payload];
    }
};
</script>
<template>
    <div class="size-full flex flex-col">
        <div
            v-if="item.training"
            :class="['box h-full flex flex-col text-nowrap text-sm ', isOutdated(item) ? '!bg-gray-200' : 'bg-rainbow text-white']"
            @click="$emit('select', item)"
        >
            <p
                v-if="item.training?.cancellation"
                :class="['relative z-1 -mb-2 text-2xs text-wrap rounded-t-lg border-none p-0.5', 'status-' + CancelStatus[3].class]"
            >
                {{ CancelStatus[item.training.cancellation.status].desc }}
            </p>
            <ItemCard
                :item="item"
                :title="item?.training?.student?.user?.name || 'N/A'"
                :hint="isOutdated(item) ? 'Passé' : 'Séance'"
                :class="[
                    isOutdated(item) ? 'from-gray-200 to-slate-100 !ring-0 text-dark saturate-0' : 'bg-dark !ring-0 text-white  bg-rainbow',
                    item.training?.cancellation && 'pt-3',
                ]"
                class-name="text-white"
                :icon="CalendarCheckIcon"
            />
        </div>
        <ItemCard
            v-else-if="state.student && isActive"
            :item="item"
            :title="state.student.user?.name || 'N/A'"
            hint="Propo"
            class="from-blue-500 to-cyan-500 ring-blue-500/10 text-white"
            class-name="text-blue-500"
            :icon="AffiliateIcon"
            @click="onAddProposal"
        />
        <ItemCard
            v-else
            :item="item"
            title="Disponible a"
            hint="Dispo"
            class="from-gray-50 to-slate-100 ring-green-500 !ring-0 border border-dashed border-green-500 text-green-500"
            class-name="text-green-500 ring-1 ring-green-300"
            :icon="StatusActiveIcon"
            @click="!state.student ? $emit('select', item) : onAddProposal()"
        />
    </div>
</template>
