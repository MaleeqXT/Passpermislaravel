<script setup lang="ts">
import { CheckIcon } from '@adersolutions/icons';
import { Card, Button, EmptyState, Drawer, RadioField } from '@shared/components';
import { routes as adminRoute } from '@espace-admin/routes';
import { useStudentSpace } from '@espace-student/stores';
import { AreaType } from '@common/types';
const emit = defineEmits(['close']);
defineProps({
    show: Boolean,
});
const { areaQuery, placesQuery, locations } = useStudentSpace();

const onChangeZone = () => {
    locations.place = '';
    locations.area && placesQuery.fetch(route(adminRoute.api.locations.places.index, locations.area));
};
const onClose = (refreshData = null) => {
    locations.show = false;
    emit('close', refreshData);
};

// const onAddPlace = (value: string) => {
//     const index = locations.place.findIndex((id) => id === value);
//     if (index > -1) {
//         locations.place = locations.place.filter((id) => id !== value);
//     } else {
//         locations.place.push(value);
//     }
// };
const onSave = () => {
    locations.set();
    onClose();
};
</script>

<template>
    <Drawer :show="!!locations.show" title="Filtres" :closeable="!!locations.place" @close="onClose">
        <template #actions>
            <Button :disabled="!locations.place.length" link @click="onSave"> Enregistrer </Button>
        </template>

        <div class="flex-1 divide-y space-y-5 pb-20 h-full">
            <Card class="">
                <p class="px-3 bg-yellow-50 text-yellow-700 text-xs py-3">Choisissez les lieux correspondants à vos critères</p>
                <div class="block font-medium text-xs text-gray-600 mb-0.5 mt-5 px-3">Zone</div>
                <ul class="divide-y mb-6 bg-slate-100 px-3">
                    <template v-if="areaQuery.fetching">
                        <li v-for="i in 3" :key="i" class="py-2">
                            <span class="ml-9 block h-6 max-w-60 bg-gray-300 animate-pulse rounded-lg"></span>
                        </li>
                    </template>
                    <template v-else>
                        <RadioField v-model="locations.area" :items="areaQuery.data" @change="onChangeZone" />
                        <!-- <li
                            v-for="item in areaQuery.data"
                            :key="item.id"
                            :class="['flex items-center gap-3 py-2 btn-hover ']"
                            @click="onChangeZone(item)"
                        >
                            <span class="w-6 block">
                                <CheckIcon v-if="locations.area === item.id" class="w-6 text-primary" />
                            </span>
                            <span>
                                {{ item.name }}
                            </span>
                        </li> -->

                        <EmptyState v-if="!areaQuery.data?.length" as="li" title="Aucun lieu" class="bg-slate-50 py-5 rounded-xl shadow-sm">
                            <p>Selectionnez une zone pour afficher les lieux</p>
                        </EmptyState>
                    </template>
                </ul>
                <div class="block font-medium text-xs text-gray-600 mb-0.5 mt-5 px-3">Lieu</div>
                <ul class="divide-y mb-6 bg-slate-100 px-3">
                    <template v-if="placesQuery.fetching">
                        <li v-for="i in 3" :key="i" class="py-2">
                            <span class="ml-9 block h-6 max-w-60 bg-gray-300 animate-pulse rounded-lg"></span>
                        </li>
                    </template>
                    <template v-else>
                        <RadioField v-model="locations.place" :items="placesQuery.data" />

                        <!-- <li
                            v-for="item in placesQuery.data"
                            :key="item.id"
                            :class="['flex items-center gap-3 py-2 btn-hover ']"
                            @click="onAddPlace(item.id)"
                        >
                            <span class="w-6 block">
                                <CheckIcon v-if="locations.place?.includes(item.id)" class="w-6 text-primary" />
                            </span>
                            <span>
                                {{ item.name }}
                            </span>
                        </li> -->

                        <EmptyState
                            v-if="!placesQuery.data?.length"
                            as="li"
                            title="Aucun lieu"
                            class="bg-slate-50 py-5 rounded-xl shadow-sm"
                        >
                            <p>Selectionnez une zone pour afficher les lieux</p>
                        </EmptyState>
                    </template>
                </ul>
            </Card>
        </div>
    </Drawer>
</template>
