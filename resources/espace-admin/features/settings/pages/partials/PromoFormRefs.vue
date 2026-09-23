<script setup>
import { Card, DateField, TabSwitch, InputField, MediaItem } from '@shared/components';
import { toRef } from 'vue';
import { routes } from '@espace-admin/routes';
import { useQuery } from '@shared/hooks';
import { useAlert } from '@shared/stores';

const props = defineProps({
    values: Object,
});
const alert = useAlert();
const form = toRef(props, 'values');
const offersQuery = useQuery(
    {
        url: route(routes.api.offers.index),
        transformable: true,
        paginate: true,
    },
    true
);

const onAddOrRemoveProduct = (offer, isExtra = false) => {
    const data = isExtra ? form.value.extra.offers : form.value.offers;
    const index = data.indexOf(offer.id);
    if (index === -1) {
        if (data?.length >= 3) {
            alert.show({
                title: 'Vous ne pouvez pas ajouter plus de 3 produits',
                type: 'error',
            });
            return;
        }
        if (isExtra) {
            form.value.extra = {
                ...form.value.extra,
                offers: [...form.value.extra.offers, offer.id],
            };
        } else {
            form.value.offers = [...form.value.offers, offer.id];
        }
    } else {
        if (isExtra) {
            form.value.extra = {
                ...form.value.extra,
                offers: form.value.extra.offers.filter((v) => v !== offer.id),
            };
        } else {
            form.value.offers = form.value.offers.filter((v) => v !== offer.id);
        }
    }
};
</script>
<template>
    <div class="contents">
        <Card block padding>
            <div class="flex items-center justify-between gap-5">
                <div class="w-full max-w-xs">
                    <InputField v-model="form.title" placeholder="Titre ex: Promo de fin d'année" :error="form.errors.title" />
                </div>
                <div>
                    <TabSwitch
                        v-model="form.is_active"
                        :items="[
                            {
                                id: false,
                                name: 'Inactif',
                                color: 'bg-red-500',
                            },
                            {
                                id: true,
                                name: 'Actif',
                                color: 'bg-green-500',
                            },
                        ]"
                    />
                </div>
            </div>
        </Card>
        <Card block padding title="Planification">
            <div class="flex gap-5">
                <DateField
                    v-model="form.start_at"
                    label="Date de début"
                    :auto-apply="false"
                    placeholder="Choisir le début promo"
                    :error="form.errors.start_at"
                />
                <DateField
                    v-model="form.end_at"
                    label="Date de fin"
                    :auto-apply="false"
                    :min-date="new Date(form.start_at || null)"
                    placeholder="Choisir la fin promo"
                    :error="form.errors.end_at"
                />
            </div>
            <ul class="grid grid-cols-4 gap-3">
                <li class="col-span-4 flex justify-between font-medium text-xs text-gray-600 -mb-1">
                    <span>Choisir les produits de promo</span>
                    <span class="text-green-600">({{ form.offers?.length || 0 }}) Produit(s) selectionner</span>
                </li>
                <li v-for="offer in offersQuery.data" :key="offer.id">
                    <MediaItem
                        as="div"
                        :media="{ path: offer.path_img }"
                        :selected="form.offers.includes(offer.id)"
                        @click="onAddOrRemoveProduct(offer)"
                    />
                    <p class="text-3xs line-clamp-2 mt-1">{{ offer.name }}</p>
                </li>
            </ul>
        </Card>
        <Card block padding title="Supplémentaire">
            <div class="flex gap-5">
                <DateField
                    v-model="form.extra.start_at"
                    label="Date de début"
                    :auto-apply="false"
                    :min-date="new Date(form.start_at || null)"
                    :max-date="new Date(form.end_at || null)"
                    placeholder="Choisir le début promo"
                />
                <DateField
                    v-model="form.extra.end_at"
                    label="Date de fin"
                    :auto-apply="false"
                    :min-date="new Date(form.extra.start_at || null)"
                    :max-date="new Date(form.end_at || null)"
                    placeholder="Choisir la fin promo"
                />
            </div>
            <ul class="grid grid-cols-4 gap-3">
                <li class="col-span-4 flex justify-between font-medium text-xs text-gray-600 -mb-1">
                    <span>Choisir les produits de promo supplémentaire</span>
                    <span class="text-green-600">({{ form.extra.offers?.length || 0 }}) Produit(s) selectionner</span>
                </li>
                <li v-for="offer in offersQuery.data" :key="offer.id">
                    <MediaItem
                        as="div"
                        :media="{ path: offer.path_img }"
                        :selected="form.extra.offers.includes(offer.id)"
                        @click="onAddOrRemoveProduct(offer, true)"
                    />
                    <p class="text-3xs line-clamp-2 mt-1">{{ offer.name }}</p>
                </li>
            </ul>
        </Card>
    </div>
</template>
