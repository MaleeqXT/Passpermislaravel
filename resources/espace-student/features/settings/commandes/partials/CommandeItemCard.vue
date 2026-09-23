<script setup lang="ts">
import { PaymentStatus } from '@common/enums';
import { Thumb } from '@shared/components';
import { dateFormat, moneyFormat, getFilePath } from '@shared/utils';
import { computed } from 'vue';
import { ArrowRightIcon, ReceiptRefundIcon } from '@adersolutions/icons';
import { Link } from '@inertiajs/vue3';
import { routes } from '@espace-student/routes';
import { SizeEnum } from '@shared/enums';
import type { CartDetailType, SaleType } from '@common/types';

const props = defineProps<{ item: Required<SaleType> }>();
const offer = computed(() => {
    const cart = props.item.cart.cart_details as Required<CartDetailType>[];
    const cartList = cart.map((item: CartDetailType) => item.offer);
    return {
        total: cart.length,
        cart: cartList,
        item: cart.length ? cartList[0] : { name: '' },
    };
});
</script>

<template>
    <Link class="bg-white box bg-rainbow rainbow-opacity-30 overflow-clip pb-1 block" :href="route(routes.commandes.show, item.id)">
        <div class="flex justify-between gap-3 p-3 border-b-2 border-gray-400/70 border-dashed">
            <span> #{{ item.reference }} </span>
            <ArrowRightIcon class="w-6 h-6 text-gray-600" />
        </div>
        <div class="flex-1 rounded-xl p-3">
            <div class="flex gap-3">
                <Thumb :src="offer.total > 0 ? getFilePath(offer.item, true) : ReceiptRefundIcon" :size="SizeEnum.SM" />
                <div class="flex-1">
                    <p class="font-medium text-base line-clamp-1">
                        {{ offer?.item?.name }}
                    </p>
                    <p class="text-xs text-gray-600">
                        {{ dateFormat(item.created_at, 'fulltime') }}
                    </p>
                </div>
            </div>
        </div>
        <div class="flex text-xs font-semibold mx-1 text-white divide-x divide-gray-400/30 rounded-lg overflow-clip text-center">
            <span class="flex-1 p-2 bg-dark"> {{ item.balance }}h </span>
            <span class="flex-1 p-2 bg-dark">
                {{ moneyFormat(item.amount) }}
            </span>
            <span :class="['btn-' + PaymentStatus[item.payment_status].class, 'text-center !shadow-none flex-1 p-2']">
                {{ PaymentStatus[item.payment_status].name }}
            </span>
        </div>
    </Link>
</template>
