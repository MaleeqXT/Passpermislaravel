<script setup lang="ts">
import { Badge, Button, EmptyState, Popup, Thumb } from '@shared/components';
import type { OfferType } from '@common/types';
import { MenuHorizontalIcon } from '@adersolutions/icons';
import { dateFormat, getFilePath, moneyFormat } from '@shared/utils';
import { PaymentStatus } from '@common/enums';
import { Link } from '@inertiajs/vue3';
import { routes } from '@espace-secretary/routes';

defineEmits(['close']);

type PropsType = {
    items: OfferType[];
};
defineProps<PropsType>();
</script>

<template>
    <div class="mx-auto max-w-screen-lg px-4 sm:px-6 lg:px-8 w-full pb-0">
        <div class="mx-auto max-w-2xl lg:mx-0 lg:max-w-none">
            <div class="flex items-center justify-between">
                <h2 class="text-base/7 font-semibold text-gray-900">Les derniers commandes</h2>
                <Button link> Voir Tous </Button>
            </div>
            <EmptyState v-if="!items.length" block class="w-full py-20 mt-6">
                <p>Vous n'avez aucune commande</p>
            </EmptyState>
            <ul role="list" v-else class="mt-6 grid grid-cols-1 gap-x-6 gap-y-8 md:grid-cols-2 lg:grid-cols-3 xl:gap-x-8">
                <li v-for="item in items" :key="item.id" class="overflow-hidden rounded-xl border-t border-l shadow-box">
                    <div class="flex items-center gap-x-4 border-b border-gray-900/5 bg-gray-50 p-6">
                        <Thumb
                            :src="getFilePath(item.student?.user)"
                            :alt="item.student?.user.name"
                            class="size-12 flex-none rounded-lg bg-white object-cover ring-1 ring-gray-900/10"
                        />
                        <div class="text-sm/6 font-medium text-gray-900">{{ item.student?.user.name }}</div>
                        <Popup class="relative ml-auto">
                            <Button :icon="MenuHorizontalIcon" />
                            <template #content>
                                <ul class="min-w-24">
                                    <li>
                                        <Link
                                            :href="route(routes.shop.commandes.show, item.id)"
                                            :class="['block px-3 py-1 hover:bg-gray-100 text-sm/6 text-gray-900']"
                                        >
                                            Aperçu
                                        </Link>
                                    </li>
                                </ul>
                            </template>
                        </Popup>
                    </div>
                    <dl class="-my-3 divide-y divide-gray-100 p-6 text-sm/6">
                        <div class="flex justify-between gap-x-4">
                            <dt class="text-gray-500">Date de commande</dt>
                            <dd class="text-gray-700">
                                <time :datetime="item.dateTime">{{ dateFormat(item.created_at, 'fr-full') }}</time>
                            </dd>
                        </div>
                        <div class="flex justify-between gap-x-4">
                            <dt class="text-gray-500">Montant</dt>
                            <dd class="flex items-items gap-x-2">
                                <div class="font-medium text-gray-900">{{ moneyFormat(item.amount) }}</div>
                                <Badge :id="item.status" :options="PaymentStatus" />
                            </dd>
                        </div>
                    </dl>
                </li>
            </ul>
        </div>
    </div>
</template>
