<script setup lang="ts">
import { Card, DialogConfirm, Filters, Page, Badge, DataTable } from '@shared/components';
import { ArchiveIcon, UndoIcon, EditIcon, PlusIcon, DeleteIcon } from '@adersolutions/icons';
import { routes } from '@espace-admin/routes';
import { ref, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import { useRoute } from '@shared/hooks';
import { moneyFormat, getFilePath } from '@shared/utils';
import { ItemImage } from '@common/components';
import { LearningModeList, OffreTypeList, OffreCategoryList } from '@common/enums';
import { headings, tabs, actions } from './offers';
import type { OfferType } from '@common/types';
import type { BulkActionType, DataListType } from '@shared/types';

type PropsType = {
    offers: DataListType<OfferType>;
};

defineProps<PropsType>();

const params = useRoute();
const confirmation = ref<OfferType | null>(null);
const deleting = ref(false);
const state = reactive({
    confirmation: null,
    iconAction: undefined,
    typeAction: 'archiver',
});

const getAgencyPrice = (
    item: any,
    agency: 'criel' | 'toulouse'
) => {
    if (!item?.agency_pricing) return 0;

    // ✅ JSON string → array
    const pricingList = Array.isArray(item.agency_pricing)
        ? item.agency_pricing
        : JSON.parse(item.agency_pricing || '[]');

    const pricing = pricingList.find(
        (p: any) =>
            String(p.agency).trim().toLowerCase() === agency
    );

    return Number(pricing?.original_price ?? pricing?.price_ht ?? 0);
};

const getAgencyBalance = (
    item: any,
    agency: 'criel' | 'toulouse'
) => {
    if (!item?.agency_pricing) return 0;

    const pricingList = Array.isArray(item.agency_pricing)
        ? item.agency_pricing
        : JSON.parse(item.agency_pricing || '[]');

    const pricing = pricingList.find(
        (p: any) =>
            String(p.agency).trim().toLowerCase() === agency
    );

    return Number(pricing?.balance ?? 0);
};

const getAgencyMultiPayment = (
    item: any,
    agency: 'criel' | 'toulouse'
) => {
    if (!item?.agency_pricing) return 1;

    const pricingList = Array.isArray(item.agency_pricing)
        ? item.agency_pricing
        : JSON.parse(item.agency_pricing || '[]');

    const pricing = pricingList.find(
        (p: any) =>
            String(p.agency).trim().toLowerCase() === agency
    );

    return Number(pricing?.multi_payment ?? item.multi_payment ?? 1);
};


const bulkActions = (item: OfferType) => {
    const actions: BulkActionType[] = [];
    if (item.status) {
        actions.push(
            {
                label: 'Modifier',
                icon: EditIcon,
                variant: 'primary',
                href: route(routes.shop.offers.edit, item.id || '-'),
            },

            {
                label: 'Archivé',
                icon: ArchiveIcon,
                variant: 'warning',
                onAction: () => onConfirm(item),
            },

             {
                label: 'Supprimer définitivement',
                icon: DeleteIcon,
                variant: 'danger',
                onAction: () => onConfirm(item, true),
            }
        );
    } else {
        actions.push(
            {
                label: 'Restaurer',
                icon: UndoIcon,
                variant: 'dark',
                onAction: () => onConfirm(item),
            },
            {
                label: 'Supprimer définitivement',
                icon: DeleteIcon,
                variant: 'danger',
                onAction: () => onConfirm(item, true),
            }
        );
    }

    return actions;
};

const onConfirm = (item: OfferType, isDelete = false) => {
    confirmation.value = item;
    state.typeAction = item.status ? 'archiver' : isDelete ? 'supprimer' : 'restaurer';
    state.iconAction = item.status ? ArchiveIcon : isDelete ? DeleteIcon : UndoIcon;
};
const onConfirmedDelete = () => {
    deleting.value = true;
    if (state.typeAction === 'supprimer') {
        router.delete(route(routes.shop.offers.delete, confirmation.value?.id), {
            onSuccess: () => {
                confirmation.value = null;
            },
            onFinish: () => {
                deleting.value = false;
            },
        });
    } else {
        router.put(
            route(routes.shop.offers.updateStatus, confirmation.value?.id),
            {
                status: confirmation.value?.status ? 0 : 1,
            },
            {
                onSuccess: () => {
                    confirmation.value = null;
                },
                onFinish: () => {
                    deleting.value = false;
                },
            }
        );
    }
};
</script>

<template>
    <Page :actions="actions" title="Offres" width="lg">
        <Card block>
            <div>
                <Filters :default-tab="params.archived || ''" :tabs="tabs" />
              <DataTable v-slot="{ item }" :headings="headings" :items="offers" :bulk-actions="bulkActions">
    <td class="cell">
        <ItemImage
            :src="getFilePath(item, true)"
            :href="route(routes.shop.offers.edit, item.id)"
            :title="item.name"
        />
    </td>

<td class="cell">
    {{ moneyFormat(getAgencyPrice(item, 'criel')) }}
</td>

<td class="cell">
    <span v-if="getAgencyBalance(item, 'criel')">{{ Math.floor(getAgencyBalance(item, 'criel')) }} H</span>
</td>

<td class="cell">
    {{ moneyFormat(getAgencyPrice(item, 'toulouse')) }}
</td>

<td class="cell">
    <span v-if="getAgencyBalance(item, 'toulouse')">{{ Math.floor(getAgencyBalance(item, 'toulouse')) }} H</span>
</td>



    <td class="cell">
        {{ moneyFormat(item.final_price) }}
    </td>

    <td class="cell">
        <span v-if="item.discounted_price">
            {{ moneyFormat(item.final_price - item.original_price) }}
        </span>
    </td>

    <td class="cell text-center">
        {{ getAgencyMultiPayment(item, 'criel') }}
    </td>


</DataTable>

            </div>
        </Card>
        <DialogConfirm
            :loading="deleting"
            :show="!!confirmation"
            :icon="state.iconAction"
            @close="confirmation = null"
            @confirm="onConfirmedDelete"
        >
            Etes-vous sûr que vous voulez
            <b class="text-red-500">{{ state.typeAction }}</b>
            le
            <b class="text-red-500">{{ confirmation?.name }}</b>
            ?
        </DialogConfirm>
    </Page>
</template>
