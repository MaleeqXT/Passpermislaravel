<script setup lang="ts">
import { ref, reactive } from 'vue';
import { Button, Card } from '@shared/components';
import { dateFormat, moneyFormat, strip } from '@shared/utils';
import { PageMobile } from '@common/components';
import { ContractIcon, OrganizationIcon, PageDownIcon, ViewIcon } from '@adersolutions/icons';
import { InvoiceResumeHours } from './partials';
import { downloadPdf } from '@common/utils';
import { SizeEnum } from '@shared/enums';
import { InvoiceMoniteurModel } from '@espace-admin/features/settings/invoices/partials';
import { ButtonType } from '@shared/types';
// type PropsType = {
//     invoice: InvoiceType
// }
const props = defineProps({
    invoice: {
        type: Object,
        default: () => ({}),
    },
});
const invoiceContent = ref(null);
const actions: ButtonType[] = [
    {
        label: 'Telechcharger ma facture',
        variant: 'warning',
        full: true,
        icon: PageDownIcon,
        external: true,
        href: route('m.invoice', props.invoice.id),
    },
];
const state = reactive({
    show: false,
    // isFacturable: false,
});

// const handleDetailHour = (isFacturable = false) => {
//     // state.isFacturable = isFacturable;
//     state.show = true;
// };
</script>

<template>
    <PageMobile
        :title="invoice?.num_facture"
        :subtitle="`${dateFormat(invoice?.from, 'letter')} à ${dateFormat(invoice?.to, 'letter')}`"
        :width="SizeEnum.XS"
        slided
        back
        :actions="actions"
    >
        <template #nav>
            <Button :icon="ContractIcon" @click="state.show = true" variant="header" />
        </template>
        <template #header>
            <div class="px-3 pb-12">
                <h2 class="text-2xl font-bold">Le mois: {{ dateFormat(invoice?.from, 'monthly') }}</h2>
                <div class="bg-dark-block text-white/80 p-2 rounded-lg flex gap-3 items-center text-xs mt-3">
                    <OrganizationIcon class="w-10 h-10 p-2 bg-white/20 rounded-lg" />
                    <div class="flex-1">
                        <p class="flex">SAS PASSPERMISFACILE, Nº SIREN : 979 143 294</p>
                        <p>139 boulevard Déodat de Séverac, 31300 TOULOUSE</p>
                    </div>
                </div>
            </div>
        </template>
        <dl class="bg-rainbow bg-white rounded-xl shadow-xl -mt-10 z-900 grid grid-cols-3 relative overflow-clip isolate">
            <dd class="px-3 py-5">
                <p class="text-gray-600 text-xs">Facturable</p>
                <p class="text-xl font-bold mt-5">{{ strip(invoice?.details.num_heures_f) }}h</p>
            </dd>
            <dd class="text-center px-3 py-5">
                <p class="text-gray-600 text-xs">Non Facturable</p>
                <p class="text-xl font-bold mt-5">{{ strip(invoice?.details.num_heures_nf) }}h</p>
            </dd>

            <dd class="text-right px-3 py-5">
                <p class="text-gray-600 text-xs">Total dû</p>
                <p class="text-xl font-bold mt-5 text-nowrap">{{ moneyFormat(invoice?.details.total) }}</p>
            </dd>
        </dl>
        <div class="rainbow"></div>

        <Card title="Détails" as="ul" :separated="false" class="flex flex-col text-sm my-5">
            <li class="py-1.5 grid grid-cols-2 border-b">
                <span>Prix des heures</span>
                <span class="font-semibold">
                    <span class="mr-2">:</span>
                    {{ invoice?.details.prix_heure }}
                </span>
            </li>
            <li class="py-1.5 grid grid-cols-2 border-b">
                <span>Date de paiement</span>
                <span class="font-semibold">
                    <span class="mr-2">:</span>
                    {{ dateFormat(invoice?.date_paiement, 'letter') }}
                </span>
            </li>
            <li class="py-1.5 grid grid-cols-2 border-b">
                <span>Facture de (EUR)</span>
                <span class="font-semibold">
                    <span class="mr-2">:</span>
                    {{ moneyFormat(invoice?.montant) }}
                </span>
            </li>
            <li class="py-1.5 grid grid-cols-2">
                <span>Statut paeiment</span>
                <span class="font-semibold">
                    <span class="mr-2">:</span>
                    <span :class="['px-2 py-1 rounded-lg', invoice.status ? 'bg-green-500 text-white' : 'bg-gray-400']">
                        {{ invoice.status ? 'Payée' : 'En cour' }}
                    </span>
                </span>
            </li>
        </Card>

        <div class="text-xs">
            <p>Termes et Conditions</p>
            <p>Le paiement est dû dans les 30 jours suivant la date de la facture</p>
        </div>
        <InvoiceResumeHours :item="invoice" :show="state.show" @close="state.show = false" />
        <div class="hidden">
            <div ref="invoiceContent">
                <InvoiceMoniteurModel :invoice="invoice" />
            </div>
        </div>
    </PageMobile>
</template>
<style lang="scss" scoped>
.title-style p {
    @apply text-gray-800 text-sm;
}
</style>
