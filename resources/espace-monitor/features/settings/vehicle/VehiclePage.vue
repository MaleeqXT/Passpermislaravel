<script setup lang="ts">
import { reactive } from 'vue';
import { Button, EmptyState } from '@shared/components';
import { PageMobile } from '@common/components';
import { ChevronRightIcon, AlertDiamondIcon, PlusIcon } from '@adersolutions/icons';
import { CarFormDrawer } from './partials';
import { useApp } from '@shared/stores';
import { SizeEnum } from '@shared/enums';
import { CarType } from '@common/types';
type PropsType = {
    cars: CarType[];
};
type StateType = {
    selected: CarType | null;
};
defineProps<PropsType>();
const { drawer } = useApp();
const state = reactive<StateType>({
    selected: null,
});
const onClose = () => {
    state.selected = null;
};
</script>

<template>
    <PageMobile slided title="Véhicule" back :width="SizeEnum.MD">
        <template #nav>
            <button class="btn-header" @click="state.selected = {}">
                <PlusIcon class="w-5" />
            </button>
        </template>
        <div class="max-w-lg mx-auto space-y-3 mt-4 h-full w-full flex-1">
            <ul class="divide-y box bg-white">
                <li
                    v-for="item in cars"
                    :key="item.id"
                    :item="item"
                    class="flex items-center gap-3 btn-m p-2"
                    @click="state.selected = item"
                >
                    <div class="flex-1">
                        <p class="text-sm font-medium">{{ item.marque }} {{ item.modele }} {{ item.is_auto ? 'Automatique' : 'Manuel' }}</p>
                        <p class="text-xs text-gray-600">{{ item.immatriculation }}</p>
                    </div>
                    <ChevronRightIcon class="w-5" />
                </li>
            </ul>
            <EmptyState
                v-if="!cars.length"
                title="Vous n'avez pas de véhicule"
                :image="AlertDiamondIcon"
                class="py-16 md:py-10 md:mt-3 md:bg-white md:shadow-box-2 rounded-xl"
            >
                <p class="text-sm text-gray-500">essayez d'abord d'ajouter les véhicules</p>
            </EmptyState>
        </div>
        <CarFormDrawer :item="state.selected" @close="onClose" />
    </PageMobile>
</template>
