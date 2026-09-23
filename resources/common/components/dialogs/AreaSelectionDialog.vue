<script setup lang="ts">
import { reactive, watch } from 'vue';
import { ButtonGroup, Dialog, RadioField } from '@shared/components';
import { routes } from '@espace-admin/routes';
import { useQuery } from '@shared/hooks';
import type { GlobalDataType } from '@common/types';
import DataView from '../common/DataView.vue';

interface PropsType {
    form?: boolean;
    filters?: any;
    vertical?: boolean;
}
type SelectedLocationType = {
    area?: GlobalDataType | null;
    place?: GlobalDataType | null;
} | null;
const emit = defineEmits(['change']);
const selectedItem = defineModel<SelectedLocationType>({
    type: Object,
    default: () => ({}),
});

const props = withDefaults(defineProps<PropsType>(), {
    form: false,
    filters: () => ({}),
});
const state = reactive<SelectedLocationType & { show: boolean }>({
    show: false,
    area: null,
    place: null,
});
const areaQuery = useQuery({
    url: route(routes.api.locations.area.index),
});

const placesQuery = useQuery({
    transformable: true,
    callback: (data: any[] = []) => data.map((item) => ({ ...item, ...item.data })),
});
const onFetch = () => {
    areaQuery.params = props.filters || {};
    areaQuery.fetch();
};
const onOpen = () => {
    state.show = true;
    !areaQuery.meta.total && onFetch();
};
const onAreaChange = (area: any) => {
    if (!area) {
        placesQuery.data = [];
        state.place = null;
        return;
    }
    placesQuery.fetch(route(routes.api.locations.places.index, area));
};
watch(() => JSON.stringify(props.filters), onFetch);
</script>
<template>
    <div>
        <div :class="[form ? 'list-form-control' : 'filter-control']" @click="onOpen">
            <p v-if="selectedItem?.area?.name" class="flex items-center px-2 truncate flex-1 relative">
                <span :class="[form && 'form-label -m-1']"> Zone: </span>
                <b class="form-value"> {{ selectedItem.area?.name }}</b>
            </p>
            <span v-else class="flex items-center px-2 truncate flex-1">Sélectionner une zone</span>
            <p v-if="selectedItem?.place?.name" class="flex items-center px-2 truncate flex-1 relative">
                <span :class="[form && 'form-label  -m-1']"> Lieu: </span>
                <b class="form-value"> {{ selectedItem.place?.name }}</b>
            </p>
            <span v-else class="flex items-center px-2 truncate flex-1">Sélectionner un lieu</span>
        </div>
        <Dialog
            :show="state.show"
            :title="state.place?.name || 'Sélectionner le lieu et la zone'"
            z-index="z-900"
            max-width="sm"
            @close="state.show = false"
        >
            <div class="grid md:grid-cols-2 gap-1 p-1">
                <DataView :query="areaQuery" class="flex-1 p-2 bg-gray-100 box relative">
                    <RadioField
                        v-model:full="state.area"
                        :key="state.show ? 1 : 2"
                        :items="areaQuery.data"
                        label="Zone"
                        @change:full="onAreaChange"
                    />
                </DataView>
                <DataView :query="placesQuery" class="flex-1 p-2 bg-gray-100 box relative">
                    <RadioField v-model:full="state.place" :key="state.show ? 1 : 2" label="Lieu" :items="placesQuery.data" />
                </DataView>
            </div>
            <template #footer>
                <ButtonGroup
                    :actions="[
                        {
                            label: 'Annuler',
                            variant: 'secondary',
                            onAction: () => {
                                state.show = false;
                            },
                        },
                        {
                            label: 'Effacer',
                            variant: 'secondary',
                            onAction: () => {
                                emit('change', {});
                                selectedItem = {};
                                state.area = null;
                                state.place = null;
                                state.show = false;

                            },
                        },
                        {
                            label: 'Valider',
                            variant: 'primary',
                            onAction: () => {
                                const res = { area: state.area, place: state.place } as SelectedLocationType;
                                emit('change', {...res});
                                selectedItem = {...res} as SelectedLocationType;
                                state.area = res?.area || {}
                                state.place = res?.place || {}
                                state.show = false;

                            },
                        },
                    ]"
                />
            </template>
        </Dialog>
    </div>
</template>
