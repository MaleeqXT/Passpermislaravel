<script setup lang="ts">
import { Button, Dialog, DataTable, TabSwitch, Drawer, Thumb } from '@shared/components';
import { useQuery } from '@shared/hooks';
import { dateFormat, getFilePath } from '@shared/utils';
import { ref, watch } from 'vue';
import { routes } from '@espace-monitor/routes';
import { DataView } from '@common/components';
import { ReservationType } from '@common/types';
import { SizeEnum } from '@shared/enums';

defineEmits(['close']);

const props = defineProps({
    item: Object,
    show: Boolean,
});

const tabs = [
    { name: 'Factuable', id: '1' },
    { name: 'Non Facturable', id: '2' },
    { name: 'historique', id: '3' },
];
const resumeHours = useQuery<Record<string, ReservationType[]>>({
    url: route(routes.api.invoices.rapport, props.item?.id || '-'),
});
// const headings = [
//     { name: 'Condidat' },
//     { name: 'Date & Lieu', className: 'text-center' },
//     // { name: 'Lieu & Zone' },
//     { name: 'Heures', className: 'text-end' },
// ];
const selectedTab = ref(tabs[0].id);
watch(props, ({ show }) => {
    // selectedTab.value = state.isFacturable ? tabs[0].id : tabs[1].id;

    if (show) {
        // const payload = state.isFacturable ? { is_facturable: true } : { is_not_facturable: true };
        // resumeHours.fetch(null, payload);
        onChangeInvoices(selectedTab.value);
        !Object.keys(resumeHours.data).length && resumeHours.fetch();
    }
});
const onChangeInvoices = (tab: string) => {
    const payload = {};
    switch (tab) {
        case tabs[0].id:
            payload.is_facturable = true;
            break;
        case tabs[1].id:
            payload.is_not_facturable = true;
            break;
        default:
            break;
    }
    resumeHours.fetch(undefined, payload);
};
</script>

<template>
    <div class="contents">
        <Drawer :show="show" @close="$emit('close')" title="rapport des heures">
            <div class="px-2">
                <TabSwitch v-model="selectedTab" :items="tabs" full size="sm" class="p-1 text-nowrap" @change="onChangeInvoices" />
            </div>
            <div class="flex-1 pb-20">
                <DataView :empty="Object.keys(resumeHours.data).length" :query="resumeHours">
                    <ul role="list" class="space-y-5 mt-5 px-3 relative">
                        <div :class="['bottom-10 absolute left-3 top-0 flex w-6 justify-center']">
                            <div class="w-px bg-gray-300" />
                        </div>
                        <template v-for="(group, date) in resumeHours.data" :key="date">
                            <li class="relative flex gap-x-3.5">
                                <div class="relative flex size-6 flex-none items-center justify-center bg-gray-100">
                                    <div class="size-1.5 rounded-full bg-gray-200 ring-1 ring-gray-400" />
                                </div>
                                <p class="flex-auto py-0.5 text-xs/5 text-gray-500">
                                    Le <b class="text-dark">{{ dateFormat(date, 'full') }}</b>
                                </p>
                            </li>

                            <li v-for="item in group" :key="item.id" class="relative flex gap-x-2.5">
                                <!-- <div :class="['-bottom-8', 'absolute left-0 top-0 flex w-6 justify-center line']">
                                    <div class="w-px bg-gray-300" />
                                </div> -->
                                <div
                                    class="relative min-w-0 h-fit flex-none rounded-full flex-center flex-col ring-4 ring-gray-100 -ml-[3.5px]"
                                >
                                    <Thumb :src="getFilePath(item.training?.student?.user)" :size="SizeEnum.XS" class="!rounded-full" />
                                </div>
                                <dl :class="['flex-auto rounded-lg ring-1 ring-inset ring-gray-200 ']">
                                    <div class="flex -mt-4 text-2xs">
                                        <p class="flex-1 truncate">À {{ item.lieu?.name }}</p>
                                        <time :datetime="item.date" class="text-primary font-semibold">
                                            {{ item.start_at }} - {{ item.end_at }}
                                        </time>
                                    </div>
                                    <dd :class="[' text-xs/5 flex justify-between gap-3 py-1 px-2', 'bg-dark/5 text-gray-800']">
                                        <div class="py-0.5 text-xs/5 text-gray-500">
                                            {{ item.hour }}h Avec Condidat
                                            <span class="font-medium text-gray-900">{{ item.training?.student?.user?.name }}</span>
                                        </div>
                                    </dd>
                                    <dd class="flex justify-between gap-x-4 p-2">
                                        <div class="py-0.5 text-xs/5 text-gray-500">
                                            {{ item.review_monitor?.comment }}
                                        </div>
                                    </dd>
                                </dl>
                                <!-- <ScheduledReservationItem :item="item" @click="details.open(item)" /> -->
                            </li>
                        </template>
                    </ul>
                </DataView>
                <!-- <DataTable v-slot="{ item: resume }" :items="resumeHours" :paginate="true" :headings="headings" class="w-full text-sm">
                    <td class="p-1">
                        <b>{{ resume.session?.student?.user?.name }}</b>
                        <p class="text-xxs">Offre: {{ resume.training?.offer?.name }}</p>
                        <p class="text-xxs">Lieu: {{ resume.lieu?.name }}, {{ resume.lieu?.zone?.name }}</p>
                    </td>
                    <td class="p-1 text-center">
                        <p>
                            {{ dateFormat(resume.date, 'letter') }}
                        </p>
                        <p class="text-xxs">{{ resume.start_at }} à {{ resume.end_at }}</p>
                    </td>
                    <td class="p-1 pr-3 font-bold text-end">{{ resume.hour }}h</td>
                </DataTable> -->
                <Button
                    v-if="resumeHours.links?.next && item"
                    link
                    info
                    full
                    class="mx-auto my-4"
                    :loading="resumeHours.fetchingMore"
                    @click="resumeHours.fetch(null, {}, true)"
                >
                    Afficher plus
                </Button>
            </div>
        </Drawer>
    </div>
</template>
