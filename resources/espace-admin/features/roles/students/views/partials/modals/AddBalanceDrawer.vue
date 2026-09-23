<script setup lang="ts">
import { ChevronLeftIcon } from '@adersolutions/icons';
import { Card, Button, Drawer, Select, InputField, Thumb } from '@shared/components';
import { useMutation, useQuery } from '@shared/hooks';
import { ItemImage,ListOffersDialog } from '@common/components';
import { routes } from '@espace-admin/routes';
import { getFilePath, moneyFormat } from '@shared/utils';
import type { OfferType } from '@common/types';
import {WalletOffreType} from "@common/types";
import { computed, ref, watch } from 'vue';

const emit = defineEmits(['close', 'newBalance']);
const props = defineProps({
    show: Boolean,
    user: {
        type: Object,
        default: () => ({}),
    },
});

const form = useMutation({
    status: 'inc',
    balance: props.user?.student?.balance || 0,
    offer_id: null,
});

// ✅ Helper to calculate per-installment amount safely
const perInstallment = (amount: number | string, multi: number | string) => {
  const a = Number(amount) || 0;
  const m = Number(multi) || 1;
  if (m <= 0) return a;
  return Math.round((a / m) * 100) / 100;
};




const offersQuery = useQuery({
  url: route(routes.api.offers.index, {
    is_offer_cart: true,
    boite_type: props.user?.student?.boite_type,
  }),
}, true);

watch(
  () => props.user?.student?.boite_type,
  (newVal) => {
    if (newVal !== undefined && newVal !== null) {
      offersQuery.fetch(
        route(routes.api.offers.index, {
          is_offer_cart: true,
          boite_type: newVal,
        })
      );
    }
  },
  { immediate: true }
);



const balancesQuery = useQuery(
    {
        url: route(routes.api.balances.getBalanceByStudent, props.user?.student?.id || '-'),
        transformable: true,
        callback: (data = []) => {
            return data.map((wallet: any) => {
                const offer = wallet.offer || wallet;
                const oneTimeBalance = Number(wallet.one_time_balance || 0);
                const installmentTotalBalance = Number(wallet.installment_total_balance || 0);
                const perInstallmentBalance = Number(wallet.per_installment_balance || 0);
                const totalBalance = Number(wallet.total_balance || 0);
                const installmentCount = Number(wallet.installments || wallet.multi_payment || 1);

                return {
                    ...wallet,
                    ...offer,
                    one_time_balance: oneTimeBalance,
                    installment_total_balance: installmentTotalBalance,
                    per_installment_balance: perInstallmentBalance,
                    total_balance: totalBalance,
                    installments: installmentCount,
                };
            });
        },
    },
    true
);



// Track selected offer
const selectedOffer = ref<OfferType | null>(null);

// ✅ When user clicks an installment, fill its divided balance
const setInstallmentBalance = (index: number) => {
  if (!selectedOffer.value) return;
  const amount = perInstallment(
    selectedOffer.value.balance,
    selectedOffer.value.multi_payment
  );
  form.balance = amount;
};

// ✅ For one-time offers
const setOneTimeBalance = () => {
  if (!selectedOffer.value) return;
  form.balance = selectedOffer.value.balance;
};

const handleSelectedProduct = (offer: OfferType) => {
  selectedOffer.value = offer;
  form.balance = offer.balance;
};


const close = () => {
    emit('close');
};

const newBalance = (balance: number) => {
    emit('newBalance', balance);
};

// ✅ Calculate total balance (one-time + installment)
const calculateTotalBalance = (item: any): number => {
    const oneTimeBalance = Number(item.one_time_balance || 0);
    const installmentTotalBalance = Number(item.installment_total_balance || 0);
    const totalBalance = oneTimeBalance + installmentTotalBalance;

    if (totalBalance > 0) {
        return totalBalance;
    }

    return parseFloat(item.balance) || 0;
};

const onSubmit = () => {
    form.mutate(route(routes.api.balances.balanceManaging, props.user?.student?.id || '-'), 'post').then((res) => {
        newBalance(res.data?.balance);
        balancesQuery.fetch();
        close();
    });
};


</script>

<template>
    <Drawer :show="show" @close="close">
        <div class="p-4 border-b grid grid-cols-2 items-center justify-start">
            <button class="btn-hover flex gap-2 items-center" @click="close">
                <ChevronLeftIcon class="w-8 p-1 bg-white shadow-box rounded-full" />
                Retour
            </button>
            <b class="flex flex-col items-center">Condidat balance</b>
        </div>
        <form class="contents" @submit.prevent="onSubmit">
            <div class="flex flex-col gap-2 p-4 h-full">
               <Card class="flex flex-col w-full mb-5">
  <Select
    v-model="form.offer_id"
    :query="offersQuery"
    :error="form.errors?.status"
    :show-search="true"
    class="w-full"
    label="Selectionner un produit"
    placeholder="produit"
    :keys="['name', 'id']"
    @change:full="handleSelectedProduct($event)"
  />

  <!-- ✅ Show divided balances (after selection) -->
  <div
    v-if="selectedOffer"
    class="mt-3 border border-gray-200 rounded-lg p-3 bg-gray-50"
  >
    <div class="flex justify-between mb-2">
      <span class="font-semibold text-sm">{{ selectedOffer.name }}</span>
      <span class="text-xs text-blue-600">
      </span>
    </div>

    <!-- If multi-payment -->
  <!-- ✅ If offer has multiple payments -->
<div v-if="selectedOffer.multi_payment > 1" class="text-xs space-y-1 text-gray-600">

  <p>
    Number of Installments :
    <b>{{ selectedOffer.multi_payment }}</b>
  </p>
  <p>
    Balance per Installment :
    <b>{{ perInstallment(selectedOffer.balance, selectedOffer.multi_payment) }}h</b>
  </p>

  <!-- ✅ Clickable installments -->
  <ul class="mt-2 space-y-1">
    <li
      v-for="i in selectedOffer.multi_payment"
      :key="i"
      class="flex justify-between text-xs border-b border-gray-100 pb-1 cursor-pointer hover:bg-blue-50 rounded px-2 transition"
      @click="setInstallmentBalance(i)"
    >
      <span>Installment {{ i }}</span>
      <span class="font-medium text-blue-600">
        {{ perInstallment(selectedOffer.balance, selectedOffer.multi_payment) }}h
      </span>
    </li>
  </ul>
</div>



    <!-- Else one-time offer -->
    <div v-else class="text-xs text-gray-600">
      <p>
        One-Time Balance :
        <b>{{ selectedOffer.balance }}h</b>
      </p>
    </div>
  </div>
</Card>


                <Card class="flex flex-col w-full mb-5">


                    <div class="flex gap-3">
                        <InputField
                            id="Balance"
                            v-model="form.balance"
                            type="number"
                            class="w-28"
                            placeholder=""
                            suffix="h"
                            label="Solde"
                        />
                        <Select
                            v-model="form.status"
                            :items="[
                                { id: 'inc', name: 'Addition (+)' },
                                { id: 'dec', name: 'Soustraction (-)' },
                            ]"
                            :error="form.errors?.status"
                            :show-search="false"
                            class="w-full"
                            label="Operation"
                            placeholder="Operation"
                        />
                    </div>
                </Card>

                <Card class="" title="La list des balances" block padding>
    <ul class="divide-y">
      <li v-for="item in balancesQuery.data || []" :key="item.id" class="py-2">
        <div class="flex items-center gap-3">
          <Thumb :src="getFilePath(item.offer, true)" size="md" />
          <div class="flex-1">
            <b class="text-md flex justify-between">
              {{ item?.offer?.name }}
              <small class="text-blue-500">{{ item.total_balance }} H total</small>
            </b>

            <p class="line-clamp-2 max-w-xs whitespace-break-spaces text-2xs mt-2" v-html="item?.offer?.description"></p>
          </div>
        </div>
      </li>
    </ul>
  </Card>
                <div class="flex-1"></div>
            </div>
            <div class="flex flex-row justify-between py-2 px-4 text-right flex-shrink-0 h-fit sticky bottom-0 bg-white shadow-box">
                <Button variant="primary" submit full :disabled="!form.isDirty" :loading="form.mutating"> Enregistrer </Button>
            </div>
        </form>
    </Drawer>
</template>
