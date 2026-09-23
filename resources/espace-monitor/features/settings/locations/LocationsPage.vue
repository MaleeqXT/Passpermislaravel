<script setup lang="ts">
import { PageMobile, SelectLocationDialog } from '@common/components';
import { Card, Button, EmptyState, Errors, Select, Dialog, Spinner, Drawer } from '@shared/components';
import { SearchIcon, XIcon } from '@adersolutions/icons';
import { useForm } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';
import { routes } from '@espace-monitor/routes';
import { routes as routesAdmin } from '@espace-admin/routes';
import { useQuery } from '@shared/hooks';
import { SizeEnum } from '@shared/enums';
import { AreaType } from '@common/types';
import { ButtonType } from '@shared/types';

type PropsType = {
    zones: Required<AreaType>[];
};

const props = defineProps<PropsType>();

const areaQuery = useQuery({
    url: route(routesAdmin.api.locations.area.index),
    params: { is_all: true },
});
const placesQuery = useQuery({
    // callback: (data = []) => data.map((item) => ({ ...item, ...item.data })),
    transformable: true,
    params: { is_all: true },
});
const form = useForm({
    lieux: props.zones.map((zone) => zone.lieux.map((lieu) => lieu.id)).flat() || [],
});
const state = reactive({
    modal: false,
    selectedZone: null,
});

const actions = computed<ButtonType[]>(() => [
    {
        label: 'Ajouter un lieu de rendez-vous',
        variant: 'warning',
        full: true,
        disabled: form.processing,
        onAction: () => (state.modal = true),
    },
]);

const onCloseDialog = () => {
    state.modal = false;
};

const onSubmit = () => {
    form.post(route(routes.settings.places.store), {
        preserveScroll: true,
        onSuccess: () => {
            onCloseDialog();
        },
    });
};

const onChangeZone = (id: string) => {
    if (id) {
        placesQuery.fetch(route(routesAdmin.api.locations.places.index, id));
    } else {
        placesQuery.reset();
    }
};
const isExist = (id: string) => props.zones.some((zone) => zone.lieux.some((lieu) => lieu.id === id));
</script>

<template>
    <PageMobile title="Lieux des rendez vous" width="xs" slided back :actions="actions">
        <ul class="py-5 flex flex-col gap-3">
            <li v-for="zone in zones" :key="zone.id" class="flex flex-col bg-primary/10 rounded-lg border-l-2 border-primary px-3 py-2">
                <b>{{ zone.name }}</b>
                <ul class="list-disc pl-3">
                    <li v-for="lieu in zone.lieux" :key="lieu.id" :class="[' text-sm', !lieu.status && 'opacity-70 saturate-0']">
                        <p class="leading-5 py-0.5">
                            {{ lieu.name }}
                            {{ lieu.status ? '' : '(inactif)' }}
                        </p>
                    </li>
                </ul>
            </li>
            <EmptyState v-if="!zones.length" as="li">
                <p>Vous n'avez pas encore ajouté de lieux de rendez-vous.</p>
            </EmptyState>
        </ul>
        <Drawer :show="state.modal" title="List des lieux" @close="onCloseDialog">
            <Card padding class="h-full">
                <Select
                    v-model="state.selectedZone"
                    label="Selectionner une zone"
                    position="bottom"
                    :query="areaQuery"
                    clear
                    @open="!areaQuery.data?.length ? areaQuery.fetch() : null"
                    @change="onChangeZone"
                />
                <fieldset class="mt-8 relative">
                    <legend class="text-sm leading-6 text-gray-700">Lieux</legend>
                    <div v-if="placesQuery.fetching" class="inset-0 absolute bg-white/70 backdrop-blur-sm z-1 flex-center">
                        <Spinner class="w-6 h-8" />
                    </div>
                    <div v-if="placesQuery.data.length" class="mt-1 divide-y divide-gray-200 relative box bg-white">
                        <label
                            v-for="(lieu, idx) in placesQuery.data"
                            :key="idx"
                            :for="`lieu-${lieu.id}`"
                            :class="[
                                'relative flex items-center px-3 py-2',
                                isExist(lieu.id) ? 'pointer-events-none' : 'hover:bg-gray-100 btn-m',
                            ]"
                        >
                            <span class="min-w-0 flex-1 text-sm leading-4 select-none font-medium text-gray-900">
                                {{ lieu.name }}
                                <span class="block font-light text-2xs">{{ isExist(lieu.id) ? 'Vous avez deja cette lieu' : '' }}</span>
                            </span>
                            <input
                                v-model="form.lieux"
                                :id="`lieu-${lieu.id}`"
                                :name="`lieu-${lieu.id}`"
                                :value="lieu.id"
                                type="checkbox"
                                :disabled="isExist(lieu.id)"
                                class="chx"
                            />
                        </label>
                    </div>
                    <EmptyState v-else title="Aucun lieux de rendez-vous" block class="mt-5" :image="SearchIcon">
                        <p>Essayer selectionner ou changer la zone</p>
                    </EmptyState>
                </fieldset>
                <Errors :errors="form.errors.lieux" />
            </Card>
            <div class="page-actions">
                <Button class="btn-m" variant="primary" full :loading="form.processing" :disabled="!form.isDirty" @click="onSubmit">
                    Enregistrer
                </Button>
            </div>
        </Drawer>
    </PageMobile>
</template>
<style>
.chx {
    @apply h-6 w-6 rounded-full border-gray-300 text-primary focus:ring-primary ml-3;
}
</style>
