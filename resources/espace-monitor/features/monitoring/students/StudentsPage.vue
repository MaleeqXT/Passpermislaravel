<script setup lang="ts">
import { useReservationDetails } from '@espace-monitor/stores';
import { useQuery } from '@shared/hooks';
import { routes } from '@espace-monitor/routes';
import { SearchField } from '@shared/components';
import { PageMobile } from '@common/components';
import { StudentMenuDrawer, ListStudent } from '@espace-monitor/components';
import { SizeEnum } from '@shared/enums';
type PropsType = {
    progressTotal: number;
};
defineProps<PropsType>();
const studentsQuery = useQuery({
    url: route(routes.api.students.index),
    transformable: true,
    params: {
        search: '',
    },
    mounted: true,
});
const menu = useReservationDetails();
</script>

<template>
    <PageMobile title="Candidats" :width="SizeEnum.XS">
        <template #sticky>
            <div class="p-1">
                <SearchField
                    v-model="studentsQuery.params.search"
                    :loading="studentsQuery.fetching"
                    class="bg-gray-100 rounded-xl !outline-none h-10"
                    @change="studentsQuery.fetch()"
                />
            </div>
        </template>
        <ListStudent :students="studentsQuery" :progress-total="progressTotal" @select="menu.user = $event" />
        <StudentMenuDrawer />
    </PageMobile>
</template>
