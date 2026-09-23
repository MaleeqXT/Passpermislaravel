<script setup lang="ts">
// import { PageDownIcon, ViewIcon, ReceiptRefundIcon, XIcon } from '@adersolutions/icons';
import { dateFormat, getFilePath, moneyFormat } from '@shared/utils';
import { routes } from '@espace-admin/routes';
// import { getTotalBalance } from '@espace-admin/utils';
import { PaymentStatus, PaymentStatusEnum, PaymentTypeEnum, PaymentType } from '@common/enums';
import { useRoute } from '@shared/hooks';
import { reactive } from 'vue';
import { Card, Button, Filters, DataTable, DateRangepicker, Badge, Page, Select } from '@shared/components';
// import { ItemImage } from '@common/components';
import { GeneralStatusEnum } from '@common/enums';
import { headings, tabs, type CommandesStatsType } from './commandes';
import { Link } from '@inertiajs/vue3';
import type { CommandeType } from '@common/types';
import type { DataListType } from '@shared/types';
import { SalesStats } from './partials';
import { computed } from 'vue';
import moment from 'moment-timezone';
import { ContractIcon } from '@adersolutions/icons';
type PropsType = {
    sales: DataListType<CommandeType>;
    stats: CommandesStatsType;
};

defineProps<PropsType>();

const params = useRoute();

const state = reactive({
    method: params.method || '',
    range: {
        start: params.start || moment().startOf('month').format('YYYY-MM-DD'),
        end: params.end || moment().endOf('month').format('YYYY-MM-DD'),
    },
});
const getDiffDays = computed(() => moment(state.range.end).diff(state.range.start, 'days'));

const onFilterByPayment = (value: string) => {
    // state.method = value;
    // params.set({ method: value });
};

// const checkRefundPossibility = (sid: PaymentStatusEnum, pid: PaymentTypeEnum) => {
//     return !(sid !== PaymentStatusEnum.PAID || pid !== PaymentTypeEnum.STRIPE);
// }; route('s.invoice', sale.id

const bulkActions = ({ id, status }) => [
    {
        label: 'Télécharger Facture',
        variant: 'info',
        icon: ContractIcon,
        external: true,
        disabled: status !== PaymentStatusEnum.PAID,
        href: route('s.invoice', id),
    },
];
</script>

<template>
    <Page title="Commandes" subtitle="Voir les commandes" width="xl">
        <template #center>
            <DateRangepicker
                v-model="state.range"
                class="max-w-fit mx-auto"
                placeholder="Filtre par Statistique "
                label="Filtre par date"
            />
        </template>
        <SalesStats :stats="stats" :days="getDiffDays" />

        <Card block>
            <div>
                <Filters :options="{ showSlot: true }" key-tab="status" :tabs="tabs">
                    <DateRangepicker class="max-w-fit" placeholder="Date d'ordre" title="Filtre par date" />
                    <Select
                        v-model="state.method"
                        label="Payment"
                        class="max-w-60"
                        clear
                        :items="Object.values(PaymentType)"
                        :is-filter="true"
                        @change="onFilterByPayment"
                    />
                </Filters>

                <DataTable v-slot="{ item }" :headings="headings" :items="sales" min-height="min-h-[28rem]" :bulk-actions="bulkActions">
                    <td class="cell capitalize">
                        <Link :href="route(routes.shop.commandes.show, item.id)" class="btn btn-link">#{{ item.reference }} </Link>
                    </td>
                    <td class="cell capitalize">
                        {{ dateFormat(item.created_at, 'fr-full') }}
                    </td>
                    <td class="cell">
                        <Link
                            :href="item.status === GeneralStatusEnum.INACTIVE ? '' : route(routes.users.students.general, item.student_id)"
                            class="btn btn-link btn-dark"
                        >
                            {{ item.student?.user?.name }}
                        </Link>
                    </td>

                    <td class="cell">
                        {{ moneyFormat(item.amount) }}
                    </td>
                    <td class="cell">
                        <Badge :id="item.payment_status" :options="PaymentStatus" />
                    </td>

                    <td class="cell">{{ item.cart?.cart_details?.length }} Offres</td>
                    <!-- <td class="sticky right-0 bg-white w-10 mx-auto border-l">
                        <div class="flex items-center gap-2 px-2 justify-center">
                            <RefundToolkit
                                :is-disabled="!checkRefundPossibility(item?.payment_status, item.payment_method)"
                                :order-id="item.id"
                                :balance-student="item?.student?.balance"
                                :balance-order="getTotalBalance(item.cart?. cart_details)"
                            >
                                <div
                                    :class="
                                        checkRefundPossibility(item?.payment_status, item.payment_method)
                                            ? 'text-red-600 !bg-red-200 cursor-pointer'
                                            : 'text-gray-400 bg-gray-300/80'
                                    "
                                    class="w-7 h-7 flex-center rounded-full"
                                >
                                    <ReceiptRefundIcon
                                        :title="
                                            checkRefundPossibility(item?.payment_status, item.payment_method) ? 'Action non disponible' : ''
                                        "
                                        class="w-5"
                                    />
                                </div>
                            </RefundToolkit>
                            <Button :icon="PageDownIcon" info link title="Telechager la facture'" />
                            <Button :href="route(routes.shop.commandes.show, item.id)" :icon="ViewIcon" primary link title="Voir l'ordre" />
                        </div>
                    </td> -->
                </DataTable>
            </div>
        </Card>
    </Page>
</template>
