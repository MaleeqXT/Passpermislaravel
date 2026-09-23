<script setup lang="ts">
import { Page } from '@shared/components';
import { OfferType, ReservationType } from '@common/types';
import { LastCommandesCard, LastReservationCard } from './partials';

type PropsType = {
    totalPerMonth: {
        students: number;
        reservations: number;
        commmandes: number;
        balances: number;
    };
    lastCommandes?: OfferType[];
    lastReservations: Record<string, ReservationType[]>;
};
const props = withDefaults(defineProps<PropsType>(), {
    totalPerMonth: () => ({
        students: 0,
        reservations: 0,
        commmandes: 0,
        balances: 0,
    }),
    lastCommandes: () => [],
    lastReservations: () => ({}),
});

const stats = [
    { name: 'Candidats', value: props.totalPerMonth.students },
    { name: 'Réservations', value: props.totalPerMonth.reservations },
    { name: 'Commandes', value: props.totalPerMonth.commmandes },
    { name: 'balances', value: props.totalPerMonth.balances },
];
</script>
<template>
    <Page padding="none" width="full" class="pb-20">
        <div class="isolate overflow-hidden mt-5 mx-auto max-w-screen-lg px-4 sm:px-6 lg:px-8 w-full">
            <!-- Stats -->
            <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 border-b bg-gray-200 box bg-rainbow rainbow-opacity-50 relative">
                <div
                    v-for="(stat, statIdx) in stats"
                    :key="stat.name"
                    :class="[
                        statIdx % 2 === 1 ? 'sm:border-l' : statIdx === 2 ? 'lg:border-l' : '',
                        'flex flex-wrap items-baseline justify-between gap-x-4 gap-y-2 border-t border-gray-900/5 px-4 py-5 sm:px-6 lg:border-t-0 xl:px-8',
                    ]"
                >
                    <dt class="text-sm/6 font-medium opacity-70">{{ stat.name }}</dt>

                    <dd class="w-full flex-none text-3xl/10 font-medium tracking-tight text-gray-900">{{ stat.value }}</dd>
                </div>
                <div
                    class="absolute left-0 top-full -z-10 mt-96 origin-top-left translate-y-40 -rotate-90 transform-gpu opacity-20 blur-3xl sm:left-1/2 sm:-ml-96 sm:-mt-10 sm:translate-y-0 sm:rotate-0 sm:transform-gpu sm:opacity-50"
                    aria-hidden="true"
                >
                    <div class="aspect-[1154/678] w-[72.125rem] bg-gradient-to-br from-primary to-dark clippath" />
                </div>
            </dl>
        </div>
        
        <LastReservationCard :items="lastReservations" />
        <LastCommandesCard :items="lastCommandes" />
    </Page>
</template>
