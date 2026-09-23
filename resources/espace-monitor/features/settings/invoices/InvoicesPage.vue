<script setup lang="ts">
import { Badge, DateField, EmptyState, Spinner } from '@shared/components';
import { PageMobile } from '@common/components';
import { routes } from '@espace-monitor/routes';
import { useRoute, useQuery } from '@shared/hooks';
import { dateFormat } from '@shared/utils';
import { reactive } from 'vue';
import moment from 'moment-timezone';

defineProps({
    totalHour: Number,
});
const params = useRoute<{ period: string; is_facturable: string; is_not_facturable: string }>();
const state = reactive({
    period: params.period || moment().add(-1, 'month').format('yyyy-MM'),
});
const resumeHours = useQuery({
    url: route(routes.api.invoices.rapport),
    params: {
        is_facturable: params.is_facturable,
        is_not_facturable: params.is_not_facturable,
        period: state.period,
    },
    mounted: true,
});

const onChangePeriod = (period: string) => {
    resumeHours.params.period = period;
    resumeHours.fetch();
    params.set({ period });
};
</script>

<template>
    <PageMobile :title="params.is_facturable ? 'Facturable' : 'Non Facturable'" subtitle="Liste des factures" back slided>
        <template #nav>
            <DateField
                v-model="state.period"
                month-picker
                class="flex-1 !w-auto"
                :max-date="moment().add(1, 'month').format('yyyy-MM-DD')"
                custom-class="bg-gray-300/30 shadow-sm font-bold text-white text-[20px] h-10 max-w-[10rem] rounded-full"
                @change="onChangePeriod"
            />
        </template>
        <div class="my-5 mx-auto max-w-xl w-full h-full flex-1">
            <div v-if="resumeHours.fetching" class="flex-center py-20">
                <Spinner class="w-6 h-6" />
            </div>
            <EmptyState
                v-else-if="!resumeHours.data.length"
                class="pb-5"
                heading="Aucun factures pour le moment"
                image="https://cdn.shopify.com/s/files/1/0262/4071/2726/files/emptystate-files.png"
            >
                <p>Vous n'avez pas encore de factures.</p>
            </EmptyState>
            <div v-else class="flex flex-col gap-3">
                <div v-for="item in resumeHours.data" :key="item.id" class="bg-white rounded-xl shadow-box-2 overflow-hidden">
                    <div class="flex justify-between border-b bg-gray-50 p-2 text-xs">
                        <p>{{ dateFormat(item.date, 'letter') }} de {{ item.start_at }} à {{ item.end_at }}</p>
                        <p>{{ item.hour }}h {{ params.is_not_facturable ? 'Non ' : '' }} facturables</p>
                    </div>
                    <div class="p-2">
                        <b>{{ item.training?.student?.user?.name }}</b>
                        <div v-if="params.is_facturable">
                            <p v-if="item.review_monitor?.comment" v-html="item.review_monitor?.comment"></p>
                            <Badge v-if="item.review_monitor?.is_absent" info> Absent </Badge>
                        </div>
                    </div>
                    <div class="flex max-md:flex-col md:justify-between border-t p-2 text-xs">
                        <p>Offre: {{ item.training?.offer?.name }}</p>
                        <p>Lieu: {{ item.lieu?.name }}, {{ item.lieu?.zone?.name }}</p>
                    </div>
                </div>
                <div class="bg-orange-100 text-lg font-semibold rounded-xl shadow-box-2 p-2 flex justify-between sticky bottom-3">
                    Total Heures {{ params.is_facturable ? 'Non ' : '' }}Facturable:
                    <b> {{ totalHour || '' }}h</b>
                </div>
                <button v-if="resumeHours.links.next" class="mx-auto btn btn-dark" @click="resumeHours.fetchNext()">Voir plus</button>
            </div>
        </div>
    </PageMobile>
</template>
