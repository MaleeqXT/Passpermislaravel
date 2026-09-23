<script setup lang="ts">
import { EmptyState, Popup, Thumb } from '@shared/components';
import { MenuHorizontalIcon } from '@adersolutions/icons';
import { dateFormat, getFilePath, moneyFormat } from '@shared/utils';
import { CancelStatus, PaymentStatus } from '@common/enums';
import { Link } from '@inertiajs/vue3';
import { routes } from '@espace-admin/routes';
import type { ReservationType } from '@common/types';
import { SizeEnum } from '@shared/enums';

defineEmits(['close']);

type PropsType = {
    items: Record<string, NonNullable<ReservationType>[]>;
};
defineProps<PropsType>();
const statuses = {
    Paid: 'text-green-700 bg-green-50 ring-green-600/20',
    Withdraw: 'text-gray-600 bg-gray-50 ring-gray-500/10',
    Overdue: 'text-red-700 bg-red-50 ring-red-600/10',
};
</script>

<template>
    <div class="space-y-16 py-16 xl:space-y-20">
        <!-- Recent activity table -->
        <div>
            <div class="mx-auto max-w-screen-lg px-4 sm:px-6 lg:px-8">
                <h2 class="mx-auto max-w-2xl text-base font-semibold text-gray-900 lg:mx-0 lg:max-w-none">Les derniers reserevation</h2>
            </div>
            <div class="mt-6 overflow-hidden border-t border-gray-100">
                <div class="mx-auto max-w-screen-lg px-4 sm:px-6 lg:px-8">
                    <div class="mx-auto max-w-2xl lg:mx-0 lg:max-w-none">
                        <table class="w-full text-left">
                            <thead class="sr-only">
                                <tr>
                                    <th></th>
                                    <th class="hidden sm:table-cell"></th>
                                    <th>More</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template v-for="(group, key) in items" :key="key">
                                    <tr class="text-sm/6 text-gray-900">
                                        <th scope="colgroup" colspan="3" class="relative isolate py-2 font-semibold">
                                            <time :datetime="key">{{ dateFormat(key, 'letter') }}</time>
                                            <div class="absolute inset-y-0 right-full -z-10 w-screen border-b border-gray-200 bg-gray-50" />
                                            <div class="absolute inset-y-0 left-0 -z-10 w-screen border-b border-gray-200 bg-gray-50" />
                                        </th>
                                    </tr>
                                    <tr v-for="item in group" :key="item.id">
                                        <td class="relative py-5 pr-6">
                                            <div class="flex gap-x-6">
                                                <Thumb
                                                    :src="getFilePath(item.training?.student?.user)"
                                                    class="hidden sm:block"
                                                    aria-hidden="true"
                                                    :size="SizeEnum.SM"
                                                />
                                                <div class="flex-auto">
                                                    <div class="flex items-start gap-x-3">
                                                        <div class="text-sm/6 font-medium text-gray-900">
                                                            <p
                                                                v-if="item?.training?.cancellation"
                                                                :class="[
                                                                    'text-2xs/3 rounded w-fit status-' +
                                                                        CancelStatus[item.training.cancellation.status].class,
                                                                ]"
                                                                :tooltip="CancelStatus[item.training.cancellation.status].desc"
                                                            >
                                                                Annulation : {{ CancelStatus[item.training.cancellation.status].name }}
                                                            </p>
                                                            <b>
                                                                {{ dateFormat(item.datef, 'letter') }} - {{ item.start_at }} à
                                                                {{ item.end_at }}
                                                            </b>
                                                        </div>
                                                    </div>
                                                    <div class="mt-1 text-xs/5 text-gray-500">
                                                        à {{ item.lieu?.zone?.name }}, {{ item.lieu?.name }}
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="absolute bottom-0 right-full h-px w-screen bg-gray-100" />
                                            <div class="absolute bottom-0 left-0 h-px w-screen bg-gray-100" />
                                        </td>
                                        <td class="hidden py-5 pr-6 sm:table-cell">
                                            <div class="text-sm/6 text-gray-900">
                                                Reservation de <b> {{ item.training?.student?.user?.name ?? 'N/A' }}</b>
                                            </div>
                                            <div class="mt-1 text-xs/5 text-gray-500">
                                                Par <b>{{ item.monitor?.user?.name }}</b>
                                            </div>
                                        </td>
                                        <td class="py-5 text-right">
                                            <div class="flex justify-end">
                                                <Link
                                                    :href="
                                                        route(routes.reservations.main.index, {
                                                            student_id: item.training?.student_id,
                                                            monitor_id: item.monitor_id,
                                                        })
                                                    "
                                                    class="text-sm/6 font-medium text-indigo-600 hover:text-indigo-500"
                                                >
                                                    Voir toutes
                                                    <span class="hidden sm:inline"> les réservations</span>
                                                </Link>
                                            </div>
                                            <div class="mt-1 text-xs/5 text-gray-500">
                                                Offre: <span class="text-gray-900"> {{ item.training?.offer?.name }}</span>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                                <tr v-if="Object.keys(items).length === 0">
                                    <td :rowspan="3">
                                        <EmptyState block heading="Aucune reservation" class="w-full py-20">
                                            <p>Vous n'avez aucune reservation</p>
                                        </EmptyState>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
