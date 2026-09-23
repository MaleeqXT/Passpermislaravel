<script setup lang="ts">
import { reactive, watch } from 'vue';
import {
    Button,
    ButtonGroup,
    Dialog,
    EmptyState,
    RadioField,
    MultiCheckField,
    Spinner,
    Thumb,
    SearchField,
    Errors,
} from '@shared/components';
import { routes } from '@espace-admin/routes';
import { useQuery } from '@shared/hooks';
import type { WalletOffreType, WalletType } from '@common/types';
import { getFilePath } from '@shared/utils';

interface PropsType {
    form?: boolean;
    filters?: any;
    multi?: boolean;
    disabled?: boolean;
    label?: string;
    errors?: string[] | string;
}
type ModelType = Partial<WalletOffreType> | null | [];
const emit = defineEmits(['change']);
const selectedItem = defineModel<ModelType | WalletOffreType[]>({
    type: Object,
    default: null,
});

const props = withDefaults(defineProps<PropsType>(), {
    form: false,
    filters: () => ({}),
});
const calculateTotalBalance = (item: any): number => {
    // Use backend-calculated fields if available (same as ReservationDetailsDrawer)
    const oneTimeBalance = Number(item.one_time_balance || 0);
    const installmentTotalBalance = Number(item.installment_total_balance || 0);
    const totalBalance = oneTimeBalance + installmentTotalBalance;

    if (totalBalance > 0) {
        return totalBalance;
    }

    // Fallback to wallet balance if pre-calculated fields not available
    return parseFloat(item.balance) || 0;
};

const offersQuery = useQuery({
    transformable: true,
    callback: (data = []) => data.map((item: any) => {
        const offer = item.offer || item;
        const oneTimeBalance = Number(item.one_time_balance || 0);
        const installmentTotalBalance = Number(item.installment_total_balance || 0);
        const perInstallmentBalance = Number(item.per_installment_balance || 0);
        const totalBalance = Number(item.total_balance || 0);
        const installmentCount = Number(item.installments || item.multi_payment || 1);

        return {
            ...item,
            ...offer,
            name: offer.name,
            balance: item.balance,
            totalBalance: totalBalance || calculateTotalBalance(item),
            one_time_balance: oneTimeBalance,
            installment_total_balance: installmentTotalBalance,
            per_installment_balance: perInstallmentBalance,
            total_balance: totalBalance,
            installments: installmentCount,
        };
    }) as WalletOffreType,
});
const state = reactive<{ show: boolean; selected: ModelType }>({
    show: false,
    selected: null,
});

const onFetch = () => {
    offersQuery.params = props.filters || {};
    if (!props.filters.student_id) return;
    offersQuery.fetch(route(routes.api.balances.getBalanceByStudent, props.filters.student_id));
};

const onOpen = () => {
    state.show = true;
    !offersQuery.meta.total && onFetch();
};

watch(
    () => JSON.stringify(props.filters),
    () => {
        onFetch();
    }
);
</script>
<template>
    <div>
        <div
            :class="[form ? 'list-form-control' : 'filter-control', disabled && '!bg-gray-200 pointer-events-none opacity-70']"
            @click="onOpen"
        >
            <div v-if="Array.isArray(selectedItem) && selectedItem.length" class="flex items-center px-2 gap-4">
                <span class="truncate">{{ selectedItem.length }} Offre: </span>
                <div class="flex items-center -space-x-3 -mr-2">
                    <Thumb
                        v-for="item in (selectedItem as any[])"
                        :key="item.id"
                        class="border shadow-box hover:scale-110 t-3 hover:z-10"
                        size="2xs"
                        :tooltip="item.name"
                        :src="getFilePath(item)"
                    />
                </div>
            </div>
            <p v-else-if="selectedItem && !Array.isArray(selectedItem)" class="flex items-center px-2 gap-1 truncate">
                <span :class="[form && 'form-label']"> Offre: </span>
                <b class="form-value">{{ (selectedItem as any)?.name }}</b>
            </p>
            <span v-else class="flex items-center px-2 truncate">{{ form ? 'Selectionner une offer' : 'Filtrer Par Offres' }}</span>
        </div>
        <Errors v-if="errors" :errors="errors" />

        <Dialog
            :show="state.show"
            :title="form ? 'Choisir l\'offre' : label || 'Filtrer par un ou plusieur offres'"
            max-width="xs"
            z-index="z-900"
            @close="state.show = false"
        >
            <ul class="grid bg-gray-100">
                <li class="flex-1 flex flex-col relative h-80 overflow-y-auto p-2">
                    <SearchField
                        v-model="offersQuery.params.search"
                        class="bg-gray-200 rounded-lg mb-2 !h-9"
                        @change="offersQuery.fetch()"
                    />
                    <div v-if="offersQuery.fetching" class="bg-white/30 absolute inset-0 flex-center">
                        <Spinner class="w-5" />
                    </div>

                    <EmptyState v-else-if="!offersQuery.meta.total" />
                    <RadioField
                        v-else-if="!multi"
                        v-model:full="state.selected"
                        :items="offersQuery.data"
                    >
                        <template #content="{ item }">
                            <div class="text-sm block">
                               
                                <div class="text-gray-600 mt-1">
                                    Balance (wallet): <b>{{ item.total_balance }}</b>h total
                                </div>
                                <span v-if="item.one_time_balance > 0 && item.installment_total_balance > 0" class="text-xs text-gray-500 block mt-1">
                                    ({{ item.one_time_balance }}h one-time + {{ item.installment_total_balance }}h in {{ item.installments }} installments = {{ item.per_installment_balance }}h per installment)
                                </span>
                            </div>
                        </template>
                    </RadioField>
                    <template v-else>
                        <MultiCheckField v-model="state.selected" :items="offersQuery.data" />
                        <Button v-if="offersQuery.links.next" class="mx-auto" link variant="info" @click="offersQuery.fetchNext()">
                            Voir plus
                        </Button>
                    </template>
                </li>
            </ul>
            <template #footer>
                <ButtonGroup
                    :actions="[
                        {
                            label: 'Annuler',
                            variant: 'secondary',
                            onAction: () => {
                                state.show = false;
                            },
                        },
                        {
                            label: 'Effacer',
                            variant: 'secondary',
                            onAction: () => {
                                emit('change', multi ? [] : null);
                                selectedItem = multi ? [] : null
                                state.selected = multi ? [] : null
                            },
                        },
                        {
                            label: 'Valider',
                            variant: 'primary',
                            onAction: () => {
                                const value = JSON.parse(JSON.stringify(state.selected)) as ModelType | WalletOffreType[];
                                emit('change', value);
                                selectedItem = value
                                state.show = false;
                            },
                        },
                    ]"
                />
            </template>
        </Dialog>
    </div>
</template>
