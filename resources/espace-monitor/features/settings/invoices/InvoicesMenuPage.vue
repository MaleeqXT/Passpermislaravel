<script setup lang="ts">
import { DateField } from '@shared/components';
import { reactive } from 'vue';
import { PageMobile } from '@common/components';
import { routes } from '@espace-monitor/routes';
import { Link } from '@inertiajs/vue3';
import { MoneyNoneIcon, CalendarIcon, ChevronRightIcon, MoneyIcon } from '@adersolutions/icons';
import moment from 'moment-timezone';
import { useRoute } from '@shared/hooks';
const props = defineProps({
    invoice: {
        type: Object,
        default: () => ({}),
    },
});
const params = useRoute();
const navigations = [
    {
        name: 'Factuable',
        icon: MoneyIcon,
        content: props.invoice?.facturable_hour || 0,
        href: route(routes.invoices.index, { is_facturable: 1 }),
        id: '1',
    },
    {
        name: 'Non Facturable',
        icon: MoneyNoneIcon,
        content: props.invoice?.not_facturable_hour || 0,
        href: route(routes.invoices.index, { is_not_facturable: 1 }),
        className: 'text-red-500',
        id: '2',
    },
    { name: 'historique', href: route(routes.invoices.historique), icon: CalendarIcon, id: '3' },
];

const state = reactive({
    period: params.period || moment().add(-1, 'month').format('yyyy-MM'),
});
</script>

<template>
    <PageMobile title="Factures" :subtitle="'Les factures pour le mois de ' + state.period" back profile dark slided>
        <template #nav>
            <DateField
                v-model="state.period"
                month-picker
                class="flex-1 !w-auto"
                :max-date="moment().format('yyyy-MM-DD')"
                custom-class="bg-gray-300/30 shadow-sm font-bold text-white text-[20px] h-10 max-w-[10rem] rounded-full"
                @change="params.set({ period: $event })"
            />
        </template>
        <ul class="my-5 mx-auto max-w-xl w-full flex flex-col gap-3">
            <Link
                v-for="item in navigations"
                :key="item.id"
                as="li"
                :href="item.href"
                class="btn-m bg-white text-lg px-3 py-2 shadow-sm rounded-xl flex items-center gap-3 min-h-16"
            >
                <component :is="item.icon" class="w-7 h-7 text-primary" />
                <p class="flex-1 flex flex-col">
                    <span :class="[item.className || '']">{{ item.name }}</span>
                    <b v-if="item.content">{{ item.content }} heures</b>
                </p>
                <ChevronRightIcon class="w-5 h-5" />
            </Link>
        </ul>
    </PageMobile>
</template>
