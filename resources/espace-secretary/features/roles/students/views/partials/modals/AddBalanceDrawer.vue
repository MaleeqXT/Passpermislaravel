<script setup lang="ts">
import { ChevronLeftIcon } from '@adersolutions/icons';
import { Card, Button, Drawer, Select, InputField, Thumb } from '@shared/components';
import { useMutation, useQuery } from '@shared/hooks';
import { ItemImage, ListOffersDialog } from '@common/components';
import { routes } from '@espace-admin/routes';
import { getFilePath, moneyFormat } from '@shared/utils';
import type { OfferType } from '@common/types';
import { WalletOffreType } from "@common/types";
import { ref } from 'vue'; // Import ref

const emit = defineEmits(['close', 'newBalance']);
const props = defineProps({
    show: Boolean,
    user: {
        type: Object,
        default: () => ({}),
    },
});

// Store initial values for cancellation
const initialFormState = {
    status: 'inc',
    balance: props.user?.student?.balance || 0,
    offer_id: null,
};

const form = useMutation({ ...initialFormState });

const offersQuery = useQuery({
    url: route(routes.api.offers.index, {
        is_offer_cart: true,
    })
}, true);

const balancesQuery = useQuery(
    {
        url: route(routes.api.balances.getBalanceByStudent, props.user?.student?.id || '-'),
    },
    true
);

const handleSelectedProduct = (offer: OfferType) => {
    form.balance = offer.balance;
};

const close = () => {
    emit('close');
};

const newBalance = (balance: number) => {
    emit('newBalance', balance);
};

const onSubmit = () => {
    form.mutate(route(routes.api.balances.balanceManaging, props.user?.student?.id || '-'), 'post').then((res) => {
        newBalance(res.data?.balance);
        balancesQuery.fetch();
        close();
    });
};

// Cancel function - reset to initial values
const cancelOperation = () => {
    form.status = initialFormState.status;
    form.balance = initialFormState.balance;
    form.offer_id = initialFormState.offer_id;
    form.errors = {};
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
                    <div class="flex gap-3">

                        <InputField
                            id="Balance"
                            v-model="form.balance"
                            type="number"
                            class="w-28"
                            placeholder=""
                            suffix="h"
                            label="Solde"
                            readonly
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
                                        <small class="text-blue-500">{{ item?.balance }} H</small>
                                    </b>
                                    <p class="line-clamp-2 max-w-xs whitespace-break-spaces text-2xs" v-html="item?.offer?.description"></p>
                                </div>
                            </div>
                        </li>
                    </ul>
                </Card>
                <div class="flex-1"></div>
            </div>
            <div class="flex flex-row justify-between py-2 px-4 text-right flex-shrink-0 h-fit sticky bottom-0 bg-white shadow-box gap-2">
                <Button
                    variant="outline"
                    full
                    @click="cancelOperation"
                    :disabled="form.mutating"
                >
                    Annuler
                </Button>
                <Button variant="primary" submit full :disabled="!form.isDirty" :loading="form.mutating">
                    Enregistrer
                </Button>
            </div>
        </form>
    </Drawer>
</template>
