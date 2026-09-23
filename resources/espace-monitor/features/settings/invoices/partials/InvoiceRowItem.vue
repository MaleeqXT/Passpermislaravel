<script setup lang="ts">
import { routes } from '@espace-monitor/routes';
import { dateFormat, moneyFormat } from '@shared/utils';
import { Link } from '@inertiajs/vue3';

defineEmits(['click:resume']);
defineProps({
    item: {
        type: Object,
        default: () => ({}),
    },
});
</script>

<template>
    <Link
        as="li"
        :href="route(routes.invoices.view, item.id)"
        class="group/actions bg-white rounded-xl shadow-box-2 border overflow-hidden"
    >
        <div class="flex justify-between bg-dark text-white p-2 capitalize">
            <span>{{ dateFormat(item.created_at, 'month') }}</span>
            <span>{{ moneyFormat(item.montant) }}</span>
        </div>

        <div class="p-2">
            <b>{{ item.num_facture }}</b>

            <div class="flex gap-1 items-center mb-2 text-sm">
                <span>{{ item.monitor.details.departement || 'XX' }} </span>
            </div>
            <!-- <b>{{ item.monitor?.details?.numero_autorisation }}</b> -->
            <ul class="flex flex-col w-full divide-y bg-gray-100 rounded-xl shadow-down text-xs">
                <li class="px-2 py-1 flex justify-between">
                    Heures travaillées :
                    <b> {{ item.details?.num_heures_f }}h</b>
                </li>
                <li class="px-2 py-1 flex justify-between">
                    Tarif Enseignement :
                    <b>{{ moneyFormat(item.details?.prix_heure) }}</b>
                </li>
                <li v-if="item.date_paiement" class="px-2 py-1 flex justify-between">
                    Date Paiement:
                    <b> {{ dateFormat(item.date_paiement, 'letter') }}</b>
                </li>
            </ul>
            <!-- <Button link dark full class="text-xs mt-2" @click.stop="$emit('click:resume', item)">
                Resume des heures
            </Button> -->
        </div>
    </Link>
</template>
