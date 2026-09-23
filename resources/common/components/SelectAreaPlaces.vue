<script setup lang="ts">
import { Select } from '@shared/components';
import { useQuery } from '@shared/hooks';
import { routes } from '@espace-admin/routes';
import { reactive, watch } from 'vue';
// import { Placement } from '@popperjs/core';
const props = defineProps({
    errors: {
        type: Object,
        default: () => ({}),
    },
    defaultValue: {
        type: Object,
        default: () => ({
            lieu: null,
            zone: null,
        }),
    },
    appearance: {
        type: String,
        default: 'modal',
    },
    position: String, // Placement
    refresh: [Number, String],
    modelValue: [String, null, Number],
});
const emit = defineEmits(['update:modelValue', 'change:full']);
const data = reactive({
    zoneSelected: null,
    lieu_id: props.modelValue || null,
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

const handleFetchZones = (search = '', refetch = false, loadmore = false) => {
    if (areaQuery.data?.length && !refetch) {
        return;
    }
    areaQuery.fetch('', { search }, loadmore);
};

const onChangeZone = (zoneId) => {
    data.lieu_id = null;
    data.zoneSelected && placesQuery.fetch(route(routes.api.locations.places.index, zoneId));
};

const handleFetchLieux = (search = '', refetch = false, loadmore = false) => {
    if (placesQuery.data?.length && !refetch) {
        return;
    }
    placesQuery.fetch(route(routes.api.locations.places.index, data.zoneSelected), { search }, loadmore);
};

watch(
    () => data.lieu_id,
    (value) => {
        emit('update:modelValue', value);
    }
);
watch(
    () => props.refresh,
    () => {
        data.lieu_id = null;
    }
);
</script>
<template>
    <div :class="['relative']">
        <Select
            v-model="data.zoneSelected"
            class="flex-1"
            :position="position"
            label="Zone"
            :default-value="defaultValue.zone"
            :items="areaQuery.data || []"
            :fetching="areaQuery.fetching"
            :fetching-more="areaQuery.fetchingMore"
            ssr
            mobile
            clear
            @change="onChangeZone"
            @search="handleFetchZones($event, true)"
            @scroll:end="handleFetchZones($event, true, true)"
        />
        <Select
            :key="areaQuery.fetching ? '1' : '2' + refresh"
            v-model="data.lieu_id"
            class="flex-1 relative"
            :position="position"
            label="Lieu"
            :disabled="!data.zoneSelected"
            :default-value="defaultValue.lieu"
            :error="errors.lieu_id"
            :fetching="placesQuery.fetching"
            :items="placesQuery.data || []"
            ssr
            mobile
            clear
            @change:full="emit('change:full', $event)"
            @search="handleFetchLieux($event, true)"
            @scroll:end="handleFetchLieux($event, true, true)"
        />
        <!--       
        <template v-else>
            <select name="" id="" value="2" class="form-control">
                <option value="we">dsd</option>
                <option value="2">dsd</option>
                <option value="w3e">dsd</option>
                <option value="we4">dsd</option>
                <option value="we5">dsd</option>
                <option value="we6">dsd</option>
                <option value="we7">dsd</option>
            </select>
        </template> -->
    </div>
</template>
<style lang="scss">
.dark {
    .listbox-input {
        @apply bg-dark/30 text-white font-bold shadow-box border-white/30;
    }
}
</style>
