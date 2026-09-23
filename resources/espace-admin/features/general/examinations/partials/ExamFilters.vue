<script setup lang="ts">
import { Filters, Select } from '@shared/components';
import { ExamenStatusList, TabAll } from '@common/enums';
import { useEvents, useQuery, useRoute } from '@shared/hooks';
import { reactive } from 'vue';
import { routes } from '@espace-admin/routes';
const sortOptions = ['asc', 'desc'];
const params = useRoute();
const event = useEvents();

const monitorsQuery = useQuery({
    url: route(routes.api.monitors.all),
    transformable: true,
    callback: (data = []) => data.map((item) => ({ ...item, ...item.monitor })),
});
const studentsQuery = useQuery({
    url: route(routes.api.students.all),
    transformable: true,
    callback: (data = []) => data.map((item) => ({ ...item, ...item.student })),
});
const state = reactive({
    date_pp: params.date_pp || sortOptions[0],
});
// const handleSort = () => {
//     state.date_pp = state.date_pp === sortOptions[0] ? sortOptions[1] : sortOptions[0];
//     event.emit('table:filter:loading', { value: true });
//     params.set(
//         { date_pp: state.date_pp },
//         {
//             onFinish: () => {
//                 event.emit('table:filter:loading', { value: false });
//             },
//         }
//     );
// };
</script>

<template>
    <Filters :tabs="[TabAll, ...Object.values(ExamenStatusList)]" :default-tab="params.tab || TabAll.id">
        <Select
            :query="monitorsQuery"
            :model-value="params.monitor_id"
            placeholder="Selectionner Moniteur"
            is-filter
            label="Moniteur"
            clear
            @change="params.set({ monitor_id: $event })"
            @open="monitorsQuery.fetch()"
        />
        <Select
            :query="studentsQuery"
            :model-value="params.student_id"
            placeholder="Selectionner un eleve"
            is-filter
            label="Condidat"
            clear
            @change="params.set({ student_id: $event })"
            @open="studentsQuery.fetch()"
        />
        <!-- <button class="btn !p-0 ml-auto flex border border-gray-300 w-9 h-9 rounded-xl shadow-sm" @click="handleSort">
            <ArrowDownIcon :class="['-mr-1', state.date_pp === sortOptions[0] ? 'text-primary' : 'text-gray-500']" />
            <ArrowUpIcon :class="['-ml-1', state.date_pp === sortOptions[1] ? 'text-primary' : 'text-gray-500']" />
        </button> -->
    </Filters>
</template>
