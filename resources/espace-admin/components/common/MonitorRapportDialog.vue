<script setup lang="ts">
import { Button, Dialog, DataTable, TabSwitch } from '@shared/components';
import { useQuery } from '@shared/hooks';
import { dateFormat, getFilePath } from '@shared/utils';
import { routes } from '@espace-admin/routes';
import { ref, watch } from 'vue';
import { ItemImage } from '@common/components';

defineEmits(['close']);

const props = defineProps({
    item: [Object, null],
    href: String,
});
const resumeHours = useQuery();
const headings = [{ name: 'Heures' }, { name: 'candidat' }, { name: 'Offer' }, { name: 'Lieu' }];

const tabs = [
    { name: 'Factuable', id: 'is_facturable' },
    { name: 'Non Facturable', id: 'is_not_facturable' },
    { name: 'historique', id: 'all' },
];
const selectedTab = ref(tabs[0].id);

watch(props, ({ item }) => {
    if (item) {
        selectedTab.value = tabs[0].id;
        onChangeInvoices(tabs[0].id);
    }
});

const onChangeInvoices = (tab) => {
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
    resumeHours.fetch(
        props.href ||
            route(routes.api.monitors.factures.resumeHours, {
                monitor: props.item?.monitor_id || '-',
                billing: props.item?.id || '-',
            }),
        payload
    );
};
</script>

<template>
    <div class="contents">
        <Dialog
            :show="!!item"
            class-name="!p-0"
            custom-class="modal-mobile h-[calc(90%-3.5rem)] md:h-fit max-h-screen md:max-h-[calc(100vh-2rem)]"
            max-width="md"
            title="Resume des heures"
            subtitle="Vérifier les détails de la seance et de l'activité"
            @close="$emit('close')"
        >
            <div class="px-2">
                <TabSwitch
                    v-model="selectedTab"
                    :items="tabs"
                    class="p-1 text-nowrap sticky top-0 z-40"
                    full
                    size="md"
                    @change="onChangeInvoices"
                />
            </div>
            <div class="flex-1">
                <DataTable
                    :headings="headings"
                    :items="resumeHours"
                    :paginate="true"
                    :is-loading="resumeHours.fetching"
                    class="w-full text-sm"
                >
                    <template #items="{ items }">
                        <template v-for="(group, date) in items" :key="date">
                            <tr class="bg-white">
                                <td class="cell" :colspan="5">{{ dateFormat(date, 'letter') }}</td>
                            </tr>
                            <tr v-for="resume in group" :key="resume.id" class="border-b">
                                <td colspan="0" :class="['left-0 inset-y-1 w-1 rounded-r-full absolute']"></td>
                                <td class="cell">
                                    <span class="block">{{ resume.start_at }}</span>
                                    <span class="">{{ resume.end_at }}</span>
                                </td>

                                <td class="cell">
                                    <ItemImage
                                        :phone="resume.training?.student?.user?.phone"
                                        :src="getFilePath(resume.training?.student?.user || {})"
                                        :title="resume.training?.student?.user?.name"
                                        size="w-8"
                                    />
                                </td>
                                <td class="cell">
                                    {{ resume.training?.offer?.name }}
                                </td>
                                <td class="cell max-w-lg w-full whitespace-normal">
                                    {{ resume.lieu?.name }}
                                </td>
                                <!-- <td class="cell">
                                    <b>{{ dateFormat(item.created_at, 'month') }}</b>
                                </td>
                                <td class="cell">
                                    <Button :href="route(routes.invoices.view, item.id)" link variant="primary">
                                        <b>{{ item.num_facture }}</b>
                                    </Button>
                                </td>

                                <td class="cell">{{ strip(item.details?.num_heures) }} h</td>
                                <td class="cell">
                                    {{ moneyFormat(item.details?.total) }}
                                </td>
                                <td class="cell">
                                    {{ dateFormat(item.date_paiement, 'letter') }}
                                </td>
                                <td class="cell">
                                    <Badge
                                        :value="{
                                            name: item.date_paiement ? 'Payé' : 'En attente',
                                            class: item.date_paiement ? 'success' : 'warning',
                                        }"
                                    />
                                </td> -->
                            </tr>
                        </template>
                    </template>
                </DataTable>

                <Button
                    v-if="resumeHours.links?.next && item"
                    :loading="resumeHours.fetchingMore"
                    class="mx-auto my-4"
                    variant="info"
                    link
                    @click="resumeHours.fetch(route(routes.api.monitors.factures.resumeHours, item), {}, true)"
                >
                    Afficher plus
                </Button>
            </div>
        </Dialog>
    </div>
</template>
