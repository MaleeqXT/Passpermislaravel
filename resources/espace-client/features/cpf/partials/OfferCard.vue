<script setup lang="ts">
import { OfferType } from '@common/types';
import { PriceCircle } from '@espace-client/components';
import { Spinner } from '@shared/components';
import { useCart } from '@shared/stores';
import { getFilePath } from '@shared/utils';
import { computed } from 'vue';

type PropsType = {
    offer: OfferType;
};
const props = defineProps<PropsType>();

const emit = defineEmits<{
    (e: 'select-offer', offer: OfferType): void;
}>();
const carts = useCart();

const parsedPrice = computed(() => {
    return {
        value: props.offer.final_price?.toFixed(0),
        cent: props.offer.final_price?.toString().split('.')[1] || '',
    };
});

const isOfferInCart = computed(() => {
    return !!carts.isExist({ ...props.offer, selected_price_type: 'final' });
});

const addToCart = () => {
    if (isOfferInCart.value || carts.state.loading[props.offer.id]) return;

    // Keep the emit for backward compatibility, but the card now adds itself.
    emit('select-offer', props.offer);
    carts.add(props.offer, () => {}, 'final');
};
</script>
<template>
    <li
        class="bg-white box p-5 overflow-hidden flex flex-col"
        :style="{
            '--customcolor': offer.color,
        }"
    >
        <div class="clippath z-0 opacity-30"></div>
        <div class="relative flex flex-col z-1 flex-1">
            <img :src="getFilePath(offer, true)" :alt="offer.imageAlt" class="h-40 w-auto object-contain max-w-60 mx-auto" />
            <PriceCircle class="text-custom mx-auto">
                <span class="text-[26px] text-gray-600 text-center block -mt-4 -mb-1 font-normal tracking-normal">
                    {{ offer.balance }}h
                </span>
                <b class="flex items-baseline -mr-2">
                    {{ parsedPrice.value }}
                    <span class="text-xs tracking-normal">{{ parsedPrice.cent }}€</span>
                </b>
            </PriceCircle>
            <div class="relative my-10">
                <h3 class="text-sm font-bold text-gray-900 mb-7 underline">{{ offer.name }}</h3>
                <div class="prose prose-sm" v-html="offer.caracteristiques"></div>
            </div>
        </div>
        <button
            :class="[
                'text-white border-b-4 active:border-b-0 font-semibold h-12 w-full px-5 rounded-lg btn-m flex-center z-1 relative mb-5',
                isOfferInCart
                    ? 'border-gray-500 bg-gray-500 cursor-not-allowed'
                    : 'border-dark2 bg-custom hover:bg-dark2',
            ]"
            @click.stop="addToCart"
        >
            <span v-if="carts.state.loading[offer.id]" class="inline-flex">
                <Spinner class="h-5 w-5 animate-spin text-white" />
            </span>
            <span v-else>
                {{ isOfferInCart ? 'Déjà dans le panier' : 'Choisir cette offre' }}
            </span>
            
        </button>
        <span class="absolute bg-slate-100 w-64 h-64 rounded-full -right-28 -top-36 z-0"></span>
        <span class="absolute bg-custom w-52 h-52 rounded-full -left-28 -bottom-36 z-0 opacity-70"></span>
    </li>
</template>
