<script setup lang="ts">
import { reactive, watch } from 'vue';
import { Button, ButtonGroup, Dialog, EmptyState, RadioField, Spinner } from '@shared/components';
import { routes } from '@espace-admin/routes';
import { useQuery } from '@shared/hooks';
import type { GlobalDataType } from '@common/types';

interface PropsType {
    form?: boolean;
    show: boolean;
    processing?: boolean;
    filters?: any;
}
type ModelType = {
    area?: GlobalDataType | null;
    place?: GlobalDataType | null;
    close?: () => void;
} | null;
const emit = defineEmits(['change', 'close']);
const selectedItem = defineModel<ModelType>({
    type: Object,
    default: () => ({}),
});

const props = withDefaults(defineProps<PropsType>(), {
    filters: () => ({}),
});
const state = reactive<NonNullable<ModelType>>({
    area: {},
    place: {},
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
    !areaQuery.meta.total && onFetch();
};
const onAreaChange = (area: any) => {
    if (!area) {
        placesQuery.reset();
        state.place = null;
        return;
    }
    placesQuery.fetch(route(routes.api.locations.places.index, area));
};
watch(() => JSON.stringify(props.filters), onFetch);
watch(
    () => props.show,
    (show) => {
        show && onOpen();
    }
);

const close = () => emit('close');
</script>
<template>
    <Dialog :show="show" title="Selectionné la zone " z-index="z-900" max-width="sm" @close="emit('close')">
        <ul class="grid md:grid-cols-2 max-md:divide-y md:divide-x">
            <li class="flex-1 flex flex-col py-3 relative">
                <div class="flex-1 p-1 m-2 bg-gray-100 rounded-lg max-h-80 overflow-y-auto">
                    <div v-if="areaQuery.fetching" class="bg-gray-100/50 backdrop-blur-sm absolute inset-0 flex-center z-10">
                        <Spinner class="w-5" />
                    </div>
                    <EmptyState v-if="!areaQuery.meta.total" heading="Aucune zone disponible" />
                    <RadioField v-else v-model:full="state.area" :items="areaQuery.data" label="Zone" @change:full="onAreaChange" />
                    <Button v-if="areaQuery.links.next" class="mx-auto" link variant="info" @click="areaQuery.fetchNext()">
                        Voir plus
                    </Button>
                </div>
            </li>
            <li class="flex-1 flex flex-col py-3 relative">
                <div class="flex-1 p-2 m-2 bg-gray-100 rounded-lg max-h-60 overflow-y-auto">
                    <div v-if="placesQuery.fetching" class="bg-gray-100/50 backdrop-blur-sm absolute inset-0 flex-center z-10">
                        <Spinner class="w-5" />
                    </div>
                    <EmptyState v-if="!placesQuery.meta.total" heading="Aucun lieu disponible" />
                    <RadioField v-else v-model:full="state.place" label="Lieu" :items="placesQuery.data" />
                    <Button v-if="placesQuery.links.next" class="mx-auto" link variant="info" @click="placesQuery.fetchNext()">
                        Voir plus
                    </Button>
                </div>
            </li>
        </ul>
        <template #footer>
            <ButtonGroup
                :actions="[
                        {
                            label: 'Fermer',
                            variant: 'secondary',
                            class:'h-10',
                            onAction: () => emit('close'),
                        },
                        {
                            label: 'Valider et confirmé la disponibilité',
                            variant: 'primary',
                            class:'h-10',
                            loading: processing,
                            disabled: !state.place?.id,
                            onAction: () => {
                                const res = { area: state.area, place: state.place } as ModelType;
                                emit('change', {...res});
                                selectedItem = {...res, close} as ModelType;
                            },
                        },
                    ]"
            />
        </template>
    </Dialog>
</template>
