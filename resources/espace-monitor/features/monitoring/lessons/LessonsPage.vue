<script setup lang="ts">
import { TabSwitch } from '@shared/components';
import { LessonItem } from './partials';
import { useMonitorSpace, useReservationDetails } from '@espace-monitor/stores';
import { computed } from 'vue';
import { ReservationDetailsDrawer } from '@espace-monitor/components';
import { DataView, PageMobile, StudentStatsCard } from '@common/components';
import { routes } from '@espace-monitor/routes';
import { SizeEnum } from '@shared/enums';
import { ReservationType, StudentType } from '@common/types';
import { DataListType } from '@shared/types';
import { useRoute } from '@shared/hooks';
import { router } from '@inertiajs/vue3';
type PropsType = {
    passed: DataListType<ReservationType[]>;
    next: DataListType<ReservationType[]>;
    student: StudentType;
};
const tabs = [
    { name: 'A venir', id: '' },
    { name: 'Passée', id: '1' },
];
const space = useMonitorSpace();
const props = defineProps<PropsType>();
const details = useReservationDetails();
const params = useRoute<{ tab: string }>(false);

const student = computed(() => {
    if (details.student?.id) {
        return details.student;
    }
    return props.student || {};
});
const onProposed = () => {
    space.state.student = details.student;
    router.get(route(routes.reservations.index, { student_id: details.student.id }));
};
</script>

<template>
    <PageMobile
        :width="SizeEnum.SM"
        title="Tous les reservation"
        back
        :actions="[{ label: 'Proposer une séance', variant: 'info', full: true, onAction: onProposed }]"
    >
        <template #header v-if="student.id">
            <StudentStatsCard :student="student" class="px-3 pb-5 pt-" />
        </template>
        <template #sticky>
            <div class="flex bg-white/70 w-full py-1 px-3 backdrop-blur-md shadow-down">
                <TabSwitch v-model="params.tab" full :items="tabs" />
            </div>
        </template>
        <div class="py-3">
            <DataView v-if="params.tab === '1'" :query="passed" :empty="passed.total">
                <ul class="grid gap-2">
                    <LessonItem v-for="item in passed.data" :key="item.id" :item="item" isPassed @click="details.open(item)" />
                </ul>
            </DataView>
            <DataView v-else :query="next">
                <ul class="grid gap-2">
                    <LessonItem v-for="item in next.data" :key="item.id" :item="item" :empty="passed.total" @click="details.open(item)" />
                </ul>
            </DataView>
        </div>
        <ReservationDetailsDrawer @refresh="router.reload()" />
    </PageMobile>
</template>
