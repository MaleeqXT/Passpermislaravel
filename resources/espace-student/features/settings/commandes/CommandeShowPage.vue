<script setup lang="ts">
import { PageMobile } from '@common/components';
import { PaymentType, PaymentStatus } from '@common/enums';
import { Badge, Card, Thumb } from '@shared/components';
import { dateFormat, moneyFormat, getFilePath } from '@shared/utils';
import { routes } from '@espace-student/routes';
import { SizeEnum } from '@shared/enums';
import type { SaleType } from '@common/types';
import { computed } from 'vue';

const props = defineProps<{ sale: NonNullable<SaleType>, sales?: any[] }>();

// If multiple sales exist (one per offer), use them; otherwise use single sale
const salesArray = computed(() => {
    if (props.sales && Array.isArray(props.sales)) {
        return props.sales;
    }
    return props.sale ? [props.sale] : [];
});

const typePayment = PaymentType[props.sale.payment_method] || {};
const [primaryItem, ...restItems] = props.sale.cart?.cart_details || [];
</script>

<template>
    <PageMobile title="L'achat de votre offre" :width="SizeEnum.XS" slided :back="route(routes.commandes.index)">
        <template #header>
            <div class="px-3 pb-12">
                <Badge class="w-fit" :value="{ class: 'warning', name: `ID: ${sale.reference}` }" />
                <h2 class="text-2xl font-bold">
                    {{ primaryItem?.offer?.name }}
                </h2>
                <p>
                    {{ primaryItem?.offer?.description }}
                </p>
                <!-- <div class="bg-rose-600 text-white p-3 rounded-lg flex gap-3 items-center text-sm mt-3">
                    <NotificationIcon class="w-12 h-12 p-3 bg-white/20 rounded-lg" />
                    <div class="flex-1">
                        <p>2ème tranche de paiement le <span class="font-semibold">11 Mars 2025</span></p>
                        <button class="btn-m underline text-yellow-100 py-0.5 rounded-lg flex-center gap-2 font-bold">
                            Régler ma 2ème échéance
                            <ArrowRightIcon class="w-4" />
                        </button>
                    </div>
                </div> -->
            </div>
        </template>
        <dl class="bg-rainbow bg-white rounded-xl shadow-xl -mt-10 z-900 grid grid-cols-2 p-6 relative overflow-clip isolate">
            <dd class="">
                <p class="text-primary">Balance</p>
                <p class="text-3xl font-bold mt-3">{{ sale.balance }}h</p>
            </dd>

            <dd class="text-right px-2">
                <p class="text-primary">Total dû</p>
                <p class="text-3xl font-bold mt-3">
                    {{ moneyFormat(sale.amount) }}
                </p>
            </dd>
        </dl>
        <div class="rainbow"></div>

        <Card title="Détails" as="ul" :separated="false" class="flex flex-col text-sm my-5">
            <li class="py-1.5 grid grid-cols-2 border-b">
                <span>Date d'achat</span>
                <span class="font-semibold">
                    <span class="mr-2">:</span>
                    {{ dateFormat(sale.created_at, 'letter') }}
                </span>
            </li>
            <li class="py-1.5 grid grid-cols-2 border-b">
                <span>Method paeiment</span>
                <span class="font-semibold">
                    <span class="mr-2">:</span>
                    {{ typePayment.name }}
                </span>
            </li>
            <li class="py-1.5 grid grid-cols-2 border-b">
                <span>Type de paeiment</span>
                <span class="font-semibold">
                    <span class="mr-2">:</span>
                    Par 3 tranches
                </span>
            </li>
            <li class="py-1.5 grid grid-cols-2">
                <span>Statut paeiment</span>
                <span class="font-semibold">
                    <span class="mr-2">:</span>
                    <Badge :id="sale.payment_status" :options="PaymentStatus" />
                </span>
            </li>
        </Card>
        <Card v-if="restItems && restItems?.length" title="Les autres achat" as="ul" :separated="false" class="flex flex-col mb-20">
            <li v-for="(item, idx) in restItems" :key="idx + item.id" class="py-1.5 flex items-center gap-3 border-b last:border-b-0">
                <Thumb :src="getFilePath(item.offer, true)" :size="SizeEnum.SM" />
                <div class="flex-1 flex">
                    <h4 class="flex-1 text-sm">{{ item.offer?.name }}</h4>
                    <div class="gap-3 text-xs text-gray-700 text-right">
                        <p>{{ 'balance : ' + item.offer?.balance }} h</p>
                        <p>{{ moneyFormat(item.offer?.final_price || 0) }}</p>
                    </div>
                </div>
            </li>
        </Card>

        <a class="btn btn-primary w-full justify-center" target="_blank" :href="route('s.invoice', sale.id)">Télécharger ma facture</a>
    </PageMobile>
</template>
