<script setup lang="ts">
import { DeleteIcon } from '@adersolutions/icons';
import { Button } from '@shared/components';
import { getFilePath, moneyFormat } from '@shared/utils';
import { useCart } from '@shared/stores';
import { OfferType } from '@common/types';
import { computed } from 'vue';

type PropsType = {
    item: OfferType | any;
};

const props = defineProps<PropsType>();
const cart = useCart();

const offer = computed(() => props.item.offer || props.item);

// Calculate installment information
const installmentInfo = computed(() => {
    if (offer.value.multi_payment > 1) {
        const installmentCount = offer.value.multi_payment;
        const installmentAmount = offer.value.final_price / installmentCount;
        const totalWithFees = offer.value.final_price; // Add fee calculation here if applicable

        return {
            count: installmentCount,
            amount: installmentAmount,
            total: totalWithFees,
            hasFees: false, // Set to true if there are installment fees
        };
    }
    return null;
});
</script>

<template>
    <li class="bg-gray-100 lg:bg-white rounded-lg shadow-sm overflow-hidden">
        <!-- Payment option badge -->
        <div
            v-if="installmentInfo"
            :class="[' px-3 py-1 text-xs font-semibold', cart.state.isSpliteActive ? 'bg-primary text-white' : 'bg-gray-200 text-gray-600']"
        >
            Paiement en {{ installmentInfo.count }} fois sans frais
        </div>

        <div class="flex items-center gap-3 p-2 md:p-3">
            <!-- Offer image -->
            <img
                :src="getFilePath(offer, true)"
                class="w-12 md:w-14 aspect-square object-contain bg-gray-100 rounded-lg border border-gray-200"
                :alt="offer.name"
            />

            <!-- Offer details -->
            <div class="flex-1 min-w-0">
                <h4 class="text-sm md:text-base font-medium text-gray-900 truncate">
                    {{ offer.name }}
                </h4>

                <div class="md:mt-1 md:space-y-1">
                    <!-- Price display -->
                    <div class="flex items-baseline flex-wrap gap-x-2">
                        <span class="text-sm md:text-lg font-bold text-primary">
                            {{ moneyFormat(offer.final_price) }}
                        </span>

                        <span v-if="offer.discounted_price" class="text-3xs md:text-sm text-gray-500 line-through">
                            {{ moneyFormat(offer.original_price) }}
                        </span>
                    </div>

                    <!-- Installment details -->
                    <div v-if="installmentInfo && cart.state.isSpliteActive" class="text-xs text-gray-600">
                        <span class="font-medium"> {{ moneyFormat(installmentInfo.amount) }} × {{ installmentInfo.count }} mois </span>
                        <span v-if="installmentInfo.hasFees" class="text-warning ml-1"> (+ frais) </span>
                    </div>
                </div>
            </div>

            <!-- Remove button -->
            <Button
                variant="default"
                size="sm"
                :loading="cart.state.loading[item.id]"
                :disabled="cart.mutation.mutating || cart.query.fetching"
                @click="cart.remove(item)"
                :icon="DeleteIcon"
                class="text-gray-400 hover:text-red-500 hover:bg-gray-50"
                aria-label="Supprimer"
            />
        </div>
    </li>
</template>
