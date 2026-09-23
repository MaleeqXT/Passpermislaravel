<script setup lang="ts">
import { Dialog, Button, Select } from '@shared/components';
import { useMutation, useQuery } from '@shared/hooks';
import { routes } from '@espace-admin/routes';
import { ItemImage } from '@common/components';
import { ref } from 'vue';
import { moneyFormat } from '@shared/utils';

const props = defineProps({
    data: {
        type: [null, Object],
        default: null,
    },
});
const emit = defineEmits(['close', 'refresh']);
const { errors, form, mutate, mutating } = useMutation({
    student_id: props.data?.student_id || null,
    date: props.data?.date || null,
    start_at: props.data?.start_at || null,
    hour: 1,
    end_at: props.data?.end_at || null,
    is_active: props.data?.is_active || 1,
    monitor_id: null,
    offer_id: null,
    lieu_id: null,
});
const areaQuery = useQuery(
    {
        url: route(routes.api.locations.area.index),
    },
    true
);
const placesQuery = useQuery({
    transformable: true,
    callback: (data = []) => data.map((item) => ({ ...item, ...item.data })),
});
const monitorsQuery = useQuery(
    {
        url: route(routes.api.monitors.all),
        init: { data: props.data?.monitors || [] },
        transformable: true,
        callback: (data = []) => data.map((item) => ({ ...item, ...item.monitor })),
    },
    !props.data?.monitors?.length
);

const offersQuery = useQuery(
    {
        url: route(routes.api.balances.getBalanceByStudent, props.data?.student_id || '0'),
        transformable: true,
        callback: (data = []) =>
            data.map((item) => ({
                ...item,
                ...item.offer_id,
                real_balance: item.balance,
            })),
    },
    true
);
const zoneSelected = ref(null);

const close = () => {
    emit('close');
};
/**
 * Handle fetching the monitor data.
 *
 * @param {string} search - the search query for filtering
 * @param {boolean} refetch - whether to force a data refresh
 * @param {boolean} loadmore - whether to load more data
 * @return {void}
 */
const handleFetchMonitor = (search = '', refetch = false, loadmore = false) => {
    if (monitorsQuery.data?.length && !refetch) {
        return;
    }
    monitorsQuery.fetch('', { search }, loadmore);
};

/**
 * Function to handle fetching zones.
 *
 * @param {string} search - the search string
 * @param {boolean} refetch - whether to force a refetch
 * @param {boolean} loadmore - whether to load more data
 * @return {void}
 */
const handleFetchZones = (search = '', refetch = false, loadmore = false) => {
    if (areaQuery.data?.length && !refetch) {
        return;
    }
    areaQuery.fetch('', { search }, loadmore);
};

/**
 * Handle change in zone selection.
 *
 * @param {number} zoneId - The id of the selected zone
 * @return {void}
 */
const onChangeZone = (zoneId) => {
    form.lieu_id = null;
    zoneSelected.value && placesQuery.fetch(route(routes.api.locations.places.index, zoneId));
};

/**
 * Function for handling form submission.
 *
 * @return {void}
 */
const onSubmit = () => {
    mutate(route(routes.api.reservation.store), 'post').then(() => {
        emit('refresh');
        close();
    });
    // zoneSelected.value && placesQuery.fetch(route(routes.api.locations.places.index, zoneSelected.value));
};
</script>
<template>
    <Dialog
        :show="!!data"
        max-width="sm"
        title="Reservation"
        :subtitle="`Pour le session de ${data?.start_at || '12:00'}h à ${data?.end_at || '12:00'}h`"
        :scrollable="false"
        @close="close"
    >
        <form class="contents" @submit.prevent="onSubmit">
            <div class="px-3 pt-5 pb-8 gap-5 min-w-[27rem] border-b border-slate-400 bg-white flex-1 h-full space-y-4">
                <Select
                    v-model="zoneSelected"
                    label="Zone"
                    input-class="bg-white shadow-none border !h-9"
                    placeholder="selectionner une zone"
                    :items="areaQuery.data || []"
                    :keys="['name', 'id']"
                    :fetching="areaQuery.fetching"
                    :fetching-more="areaQuery.fetchingMore"
                    ssr
                    clear
                    @change="onChangeZone"
                    @search="handleFetchZones($event, true)"
                    @scroll:end="handleFetchZones($event, true, true)"
                />
                <Select
                    :key="areaQuery.fetching"
                    v-model="form.lieu_id"
                    label="Lieu"
                    input-class="bg-white shadow-none border !h-9"
                    placeholder="selectionner un lieu"
                    :disabled="!zoneSelected"
                    :error="errors.lieu_id"
                    :keys="['name', 'id']"
                    :show-search="false"
                    :fetching="placesQuery.fetching"
                    :items="placesQuery.data || []"
                    ssr
                    clear
                />
                <div class="grid md:grid-cols-2 gap-3">
                    <Select
                        v-slot="{ selectedItem }"
                        v-model="form.monitor_id"
                        :items="monitorsQuery.data"
                        :error="errors.monitor_id"
                        input-class="border border-slate-300 py-1 pl-3 pr-8 shadow-sm rounded-lg min-h-[2.25rem] flex items-center text-sm"
                        placeholder="Selectionner moniteur"
                        label="Moniteur"
                        ssr
                        clear
                        :keys="['name', 'id']"
                        :fetching="monitorsQuery.fetching"
                        :fetching-more="monitorsQuery.fetchingMore"
                        @search="handleFetchMonitor($event, true)"
                        @scroll:end="handleFetchMonitor($event, true, true)"
                    >
                        <ItemImage
                            class="-ml-2"
                            :title="selectedItem.name"
                            :src="selectedItem.profile_photo_url"
                            :content="selectedItem.email"
                        />
                    </Select>
                    <Select
                        v-slot="{ selectedItem }"
                        v-model="form.offer_id"
                        :error="errors.offer_id"
                        :query="offersQuery"
                        input-class="border border-slate-300 py-1 pl-3 pr-8 shadow-sm rounded-lg min-h-[2.25rem] flex items-center text-sm"
                        placeholder="Selectionner produits"
                        label="Produit"
                        clear
                    >
                        <ItemImage
                            class="-ml-2"
                            :src="selectedItem?.media?.storage_media?.path"
                            :title="selectedItem?.name"
                            :content="moneyFormat(selectedItem.real_balance)"
                        />
                    </Select>
                </div>
            </div>
            <div class="flex flex-row justify-between p-4 text-right flex-shrink-0 h-fit">
                <Button link dark @click="close">Fermer</Button>
                <Button variant="info" submit :loading="mutating">Modifier</Button>
            </div>
        </form>
    </Dialog>
</template>
