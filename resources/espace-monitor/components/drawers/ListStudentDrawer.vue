<script setup lang="ts">
import { Drawer } from '@shared/components';
import { routes } from '@espace-monitor/routes';
import { useMonitorSpace, useReservationDetails } from '@espace-monitor/stores';
import { useQuery } from '@shared/hooks';
import { ListStudent } from '../common';
import { StudentType, UserType } from '@common/types';
import { watch } from 'vue';

const { state, params, proposals } = useMonitorSpace();

const studentsQuery = useQuery({
    url: route(routes.api.students.index),
    transformable: true,
    params: {
        search: '',
    },
    mounted: true,
});
const details = useReservationDetails();

const onSelect = (item: UserType) => {
    state.student = {
        ...item.student,
        user: item,
    } as StudentType;
    if (details?.data && !details?.data?.training) {
        proposals.data = [
            {
                reservation_id: details?.data.id,
                reservation: details.data,
                student_id: item.student?.id,
                comment: '',
            },
        ];
    }
    params.set({ student_id: state.student.id });
};
watch(
    () => state.student,
    (student) => {
        console.log('student w', student);

        // if (student) {
        //     // studentsQuery.data = studentsQuery.data.filter((v) => v.id !== student.id);
        // }
    }
);
</script>

<template>
    <Drawer :show="!!(state.student && !state.student?.id)" title="Selectionné condidat" @close="state.student = null">
        <div class="px-3 flex-1">
            <ListStudent :students="studentsQuery" @select="onSelect" />
        </div>
    </Drawer>
</template>
