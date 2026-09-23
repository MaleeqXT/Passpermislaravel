<script setup lang="ts">
import { ref } from 'vue';
import { Page } from '@shared/components';
import { PageDownIcon } from '@adersolutions/icons';
import { downloadPdf } from '@common/utils';
import { InvoiceMoniteurModel } from './partials';

defineProps({
    invoice: {
        type: Object,
        default: () => ({}),
    },
});
const invoiceContent = ref(null);
</script>

<template>
    <Page
        :title="`Facture: ${invoice?.num_facture}`"
        back
        width="lg"
        :actions="[
            {
                label: 'Télécharger facture',
                icon: PageDownIcon,
                variant: 'primary',
                onAction: () => downloadPdf(invoiceContent),
            },
        ]"
    >
        <div ref="invoiceContent" class="font-pdf">
            <InvoiceMoniteurModel :invoice="invoice" />
        </div>
    </Page>
</template>
