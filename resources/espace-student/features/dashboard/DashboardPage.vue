<script setup lang="ts">
import moment from 'moment-timezone';
import { computed } from 'vue';
import { TabSwitch, Thumb, Button } from '@shared/components';
import { PageMobile } from '@common/components';
import { useApp } from '@shared/stores';
import { useStudentSpace } from '@espace-student/stores';
import { DetailsDialog, TrainingProgress, SeanceReservationList } from './partials';
import { AlertBubbleIcon } from '@adersolutions/icons';
import { SizeEnum } from '@shared/enums';
import { getFilePath } from '@shared/utils';
import { useDashboard, tabs } from './dashboard';
import { ReservationDetailsDrawer } from '@espace-student/components';
import { routes } from '@espace-student/routes';

const { user } = useApp();
const { stats } = useStudentSpace();
const { next, passed, state, todayRsv, refresh } = useDashboard();
const canShowContractButton = computed(() => Boolean(stats.data?.contract?.available));

// 🔹 CONTRACT URL
const getContractUrl = () => {
    try {
        return route(routes.settings.contract.index);
    } catch (error) {
        console.error('Unable to resolve student contract route', error);
        return '/student/settings/contrat-de-formation';
    }
};

const openContract = () => {
    const url = getContractUrl();
    if (url) {
        window.open(url, '_blank');
    } else {
        alert('❌ Contrat indisponible');
    }
};
</script>

<template>
    <PageMobile :title="`Bonjour ${user.first_name}`" subtitle="welcome back" :width="SizeEnum.MD">
        <template #header>
            <h2 class="text-2xl font-bold px-3 pt-2">
                Aperçu de vos <br />
                activités
            </h2>
            <TrainingProgress />
            <!-- ✅ CONTRACT BUTTON - HEADER SECTION -->
            <div v-if="canShowContractButton" class="px-3 py-3">
                <Button
                    full
                    @click="openContract"
                    class="bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 text-white font-semibold py-3 rounded-lg shadow-lg"
                >
                    📄 Voir Mon Contrat
                </Button>
            </div>
        </template>
        <template #sticky>
            <TabSwitch
                v-model="state.tab"
                :items="tabs"
                light
                size="lg"
                full
                class="md:mt-2 max-md:rounded-b-none shadow z-1 backdrop-blur-md p-1"
            />
        </template>
        <ul class="pt-3 flex flex-col">
            <li v-if="todayRsv" class="bg-gray-block p-3 rounded-lg mb-5 bg-rainbow" @click="state.selected = todayRsv">
                <h3 class="flex items-center gap-2"><AlertBubbleIcon class="w-7 p-1 bg-gray-200 rounded-lg" />Rappels</h3>
                <p class="mt-5 mb-2 font-semibold max-w-60 text-wrap">
                    Vous avez une séance {{ moment(`${todayRsv.datef} ${todayRsv.start_at}`, 'yyyy-MM-DD HH:mm').fromNow() }}
                </p>
                <div class="bg-gray-200 w-fit py-1 pl-2 rounded-lg flex-center gap-5 text-gray-600 text-xs">
                    <span> {{ todayRsv.start_at }}h à {{ todayRsv.end_at }}h </span>

                    <div class="flex items-center -space-x-2">
                        <Thumb class="ring-2 ring-gray-200" :src="getFilePath(todayRsv.monitor?.user)" :size="SizeEnum['2XS']" />
                        <Thumb class="ring-2 ring-gray-200" :src="getFilePath(todayRsv.training?.offer, true)" :size="SizeEnum['2XS']" />
                    </div>
                </div>
            </li>

            <SeanceReservationList
                :is-passed="state.tab === tabs[1].id"
                :reservations="{ next, passed }"
                @select="state.selected = $event"
            />
        </ul>
        <ReservationDetailsDrawer :item="state.selected" @close="state.selected = null" @refresh="refresh" />

        <!-- <DetailsDialog :item="state.selected" :is-passed="state.tab === tabs[1].id" @close="state.selected = null" /> -->
    </PageMobile>
</template>
