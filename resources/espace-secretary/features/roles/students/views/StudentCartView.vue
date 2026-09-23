<script setup lang="ts">
import { reactive } from 'vue';
import { Alerts, Back, Button, ButtonGroup, Card, EmptyState, Spinner, Thumb } from '@shared/components';
import ViewContainer from './ViewContainer.vue';
import { routes } from '@espace-admin/routes';
import { useQuery } from '@shared/hooks';
import { getFilePath, moneyFormat } from '@shared/utils';
import { CheckIcon, DeleteIcon } from '@adersolutions/icons';
import { SizeEnum } from '@shared/enums';
import { DataView } from '@common/components';
import { useCart } from '@shared/stores';
import type { OfferType, UserType } from '@common/types';

type PropsType = {
    user: UserType;
};

const props = defineProps<PropsType>();
const form = reactive({
    data: [] as Partial<OfferType>[],
});
const cart = useCart(props.user.id);

const offersQuery = useQuery({
    url: route(routes.api.offers.index, {
        is_offer_cart: true,
    }),
    mounted: true,
});

const toggleSelect = (item: OfferType) => {
    if (!form.data) {
        form.data = [];
    }
    const index = form.data?.findIndex((v) => v.id === item.id);
    if (index === -1) {
        form.data.push(item);
    } else {
        form.data.splice(index, 1);
    }
};
</script>
<template>
    <ViewContainer :user="user">
        <section class="form-page">
            <article class="left-form">
                <Back title="Pannier" :back="route(routes.users.students.index)" />
                <div class="form-content">
                    <Alerts />
                    <p class="font-medium text-sm mb-1">{{ user?.name }} Pannier</p>
                    <div class="relative">
                        <div
                            v-if="cart.query.fetching"
                            class="flex justify-center items-center w-full top-0 -bottom-1 absolute inset-0 backdrop-blur-sm z-10"
                        >
                            <Spinner class="w-6" />
                        </div>
                        <EmptyState block class="!py-12" heading="Aucune offre dans le pannier" v-if="!cart.query.data?.length">
                            <p class="text-sm">veuillez choisir les offres pour ajouter dans le pannier</p>
                        </EmptyState>
                        <Card v-else class="w-full min-h-[200px] flex-1" mb="mb-0" :separated="false" block>
                            <ul class="min-h-40 flex-1 divide-y">
                                <li v-for="item in cart.data" :key="item?.id" class="p-2 hover:bg-gray-100/70 t-2 flex gap-3">
                                    <Thumb :src="getFilePath(item.offer, true)" :size="SizeEnum.SM" />
                                    <div class="flex-1">
                                        <div class="text-sm font-semibold flex justify-between">
                                            <span>{{ item.offer?.name }}</span>
                                            <Button
                                                @click="cart.remove(item)"
                                                link
                                                variant="danger"
                                                :loading="cart.state.loading[item.id]"
                                                tooltip="Supprimer"
                                                :icon="DeleteIcon"
                                            />
                                        </div>

                                        <p
                                            v-if="item.offer?.multi_payment > 1"
                                            class="text-orange-700 bg-orange-100/70 rounded-full px-2 text-xs w-fit"
                                        >
                                            Paiement en {{ item.offer?.multi_payment }} fois
                                        </p>
                                        <span class="text-md font-bold">
                                            {{ moneyFormat(item.offer?.final_price) }}
                                        </span>
                                        <span
                                            v-if="item.offer?.multi_payment > 1"
                                            class="text-xs font-bold text-primary mb-px ml-1 opacity-80"
                                        >
                                            ({{ moneyFormat(item.offer?.final_price / (item.offer?.multi_payment || 1)) }})
                                            {{ item.offer?.multi_payment }} fois
                                        </span>
                                    </div>
                                </li>
                            </ul>
                            <div class="h-rainbow"></div>
                            <div class="bg-gray-100 w-full gap-2 p-2 text-base font-semibold">
                                <div class="flex justify-between">
                                    <span>Sous-total </span>
                                    <span> {{ moneyFormat(cart.prices.subTotal) }}</span>
                                </div>
                                <div class="flex justify-between pb-2">
                                    <span>Offers : </span>
                                    <span> {{ cart.count }}</span>
                                </div>
                                <div class="flex justify-between pt-2 border-t">
                                    <span class="text-xl font-bold">Total : </span>
                                    <span class="text-2xl font-bold"> {{ moneyFormat(cart.prices.total) }}</span>
                                </div>
                            </div>
                        </Card>
                    </div>
                </div>
            </article>
            <article class="right-form">
                <DataView :query="offersQuery">
                    <p class="font-medium text-sm mb-1">Choisissez les offres à ajouter au panier</p>
                    <Card block :separated="false" as="ul" class="divide-y overflow-y-auto scrollbar max-h-[calc(100vh-15rem)]">
                        <li
                            v-for="pr in offersQuery.data"
                            :key="pr.id"
                            :class="[
                                'px-2 py-1 t-3 btn-m flex justify-between items-center',
                                form.data.some((v) => v.id === pr.id) ? 'text-primary bg-primary/5 ' : '',
                            ]"
                            @click="toggleSelect(pr)"
                        >
                            <div class="flex gap-2 items-center">
                                <Thumb :src="getFilePath(pr, true)" :size="SizeEnum.XS" />
                                <div class="flex-1 text-sm">
                                    <p>{{ pr?.name }}</p>
                                    <p class="text-2xs text-gray-500">Prix: {{ moneyFormat(pr.final_price) }}h</p>
                                </div>
                            </div>
                            <CheckIcon v-if="form.data?.some?.((v) => v.id === pr.id)" class="w-7 h-7 text-primary" />
                        </li>
                    </Card>
                </DataView>
                <!-- <div class="flex justify-end">
                    <Button :disabled="!form.data.length" :loading="form.processing" variant="primary" @click="onSubmit">
                        Enregister
                    </Button>
                </div> -->

                <ButtonGroup
                    vertical
                    class="form-actions"
                    :actions="[
                        {
                            label: 'Enregistrer',
                            variant: 'primary',
                            onAction: () =>
                                cart.addMany(form.data || []).then(() => {
                                    form.data = [];
                                }),
                            full: true,
                            loading: cart.mutation.processing,
                            disabled: !form.data?.length,
                        },
                    ]"
                />
            </article>
        </section>
    </ViewContainer>
</template>
