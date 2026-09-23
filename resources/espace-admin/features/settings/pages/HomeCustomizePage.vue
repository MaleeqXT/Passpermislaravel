<script setup lang="ts">
import { Card, EmptyState, Select, Page, Thumb } from '@shared/components';
import { useForm } from '@inertiajs/vue3';
import { useDebounce, useQuery } from '@shared/hooks';
import { routes } from '@espace-admin/routes';
import { DeleteIcon } from '@adersolutions/icons';
import { getFilePath } from '@shared/utils';
import { ButtonGroup } from '@shared/components';
import { computed, watch } from 'vue';
import ContainerWrapper from './ContainerWrapper.vue';

const props = defineProps({
    page: {
        type: Object,
        default: () => ({}),
    },
});

const debounce = useDebounce(200);
const form = useForm({
    is_active: true,
    offers: props.page?.offers || [],
    extra: {
        offers_auto: props.page?.extra_offer_auto || [],
    },
});
const getParams = (offers = [], is_auto = 0) => ({
    url: route(routes.api.offers.index),
    transformable: true,
    paginate: true,
    params: { excluded_ids: [...offers].map((v) => v.id) },
});
const offersQuery = useQuery(getParams(form.offers), true);
const offersAutoQuery = useQuery(getParams(form.extra.offers_auto, 1), true);

const onAddProduct = (offer) => {
    if (!form.offers.some((v) => v.id === offer.id)) form.offers = [...form.offers, offer];
};
const onAddProductAuto = (offer) => {
    if (!form.extra.offers_auto.some((v) => v.id === offer.id)) form.extra = { offers_auto: [...form.extra.offers_auto, offer] };
};

const onRemoveProduct = (offer, isAuto = false) => {
    if (isAuto) {
        form.extra.offers_auto = form.extra.offers_auto.filter((item) => item.id !== offer.id);
        return;
    }

    form.offers = form.offers.filter((item) => item.id !== offer.id);
};

const onSubmit = () => {
    if (props.page?.id) {
        form.transform((data) => {
            data.offers = data.offers.map((item) => item.id);
            data.extra = {
                offers_auto: data.extra.offers_auto.map((item) => item.id),
            };
            return data;
        }).post(route(routes.pages.promo.update, props.page?.id));
    }
};
watch(
    () => form.offers,
    debounce(() => {
        offersQuery.params.excluded_ids = [...form.offers].map((v) => v.id);
        offersQuery.fetch();
    })
);
watch(
    () => form.extra.offers_auto,
    debounce(() => {
        offersAutoQuery.params.excluded_ids = [...form.extra.offers_auto].map((v) => v.id);
        offersAutoQuery.fetch();
    })
);
const actions = computed(() => [
    {
        label: 'Enregistrer',
        variant: 'primary',
        submit: true,
        disabled: !form.isDirty,
        loading: form.processing,
        onAction: onSubmit,
    },
    {
        label: 'Reintialiser',
        disabled: form.processing || !form.isDirty,
        variant: 'secondary',
        onAction: () => form.reset(),
    },
]);
</script>
<template>
    <ContainerWrapper titlse="Accueil">
        <ul class="mt-5 max-w-xl mx-auto w-full px-4">
            <Card title="Choisir 3 offres a afficher sur la page d'accueil">
                <div class="grid grid-cols-2 items-center">
                    <Select
                        :key="form.offers?.length"
                        :query="offersQuery"
                        :disabled="form.offers?.length >= 3"
                        prefix="offre"
                        clear
                        :label="`${3 - form.offers?.length} offre(s) rester`"
                        :modelValue="null"
                        @change:full="onAddProduct"
                    />
                </div>

                <ul class="flex flex-col divide-y border-b">
                    <li class="flex justify-between font-medium text-2xs text-gray-600 mb-1">
                        <span> le maximum de offre que vous pouvez ajouter est de 3 </span>
                        <span class="text-green-600">({{ form.offers?.length || 0 }}) offre(s) selectionner</span>
                    </li>

                    <li v-for="offer in form.offers" :key="offer.id" class="flex items-center py-2">
                        <div class="flex-1 flex items-center gap-2">
                            <Thumb :src="getFilePath(offer, true)" contain class="!rounded-xl" />
                            <p class="text-base line-clamp-2 mt-1">
                                {{ offer.name }}
                            </p>
                        </div>
                        <DeleteIcon class="w-6 h-6 text-red-500 cursor-pointer" @click="onRemoveProduct(offer)" />
                    </li>
                    <EmptyState
                        v-if="!form.offers?.length"
                        image="/assets/images/panel/shopping_cart.png"
                        title="Aucun offre"
                        class="pb-5 pt-3"
                    >
                        Aucun offre n'a été ajouté
                    </EmptyState>
                </ul>
            </Card>
            <!-- <Card block padding title="offres Boite automatique" subtitle="Choisir les offres de type de boite automatique">
                <div class="grid grid-cols-2 items-center">
                    <Select
                        :key="form.extra.offers_auto?.length"
                        :query="offersAutoQuery"
                        :disabled="form.extra.offers_auto?.length >= 3"
                        prefix="offre"
                        clear
                        :placeholder="`${3 - form.extra.offers_auto?.length} offre(s) rester`"
                        @change:full="onAddProductAuto"
                    />
                </div>

                <ul class="flex flex-col divide-y border-b">
                    <li class="flex justify-between font-medium text-2xs text-gray-600 mb-1">
                        <span> le maximum de offre que vous pouvez ajouter est de 3 </span>
                        <span class="text-green-600">({{ form.extra.offers_auto?.length || 0 }}) offre(s) selectionner</span>
                    </li>

                    <li v-for="offer in form.extra.offers_auto" :key="offer.id" class="flex items-center py-2">
                        <div class="flex-1 flex items-center gap-2">
                            <Thumb :src="getFilePath(offer, true)" class="!rounded-xl" />
                            <p class="text-base line-clamp-2 mt-1">
                                {{ offer.name }}
                            </p>
                        </div>
                        <DeleteIcon class="w-6 h-6 text-red-500 cursor-pointer" @click="onRemoveProduct(offer, true)" />
                    </li>
                    <EmptyState
                        v-if="!form.extra.offers_auto?.length"
                        image="/assets/images/panel/shopping_cart.png"
                        title="Aucun offre"
                        class="pb-5 pt-3"
                    >
                        Aucun offre n'a été ajouté
                    </EmptyState>
                </ul>
            </Card> -->
            <ButtonGroup :actions="actions" />
        </ul>
    </ContainerWrapper>
</template>
