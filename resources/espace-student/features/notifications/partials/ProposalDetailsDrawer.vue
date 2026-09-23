<script setup lang="ts">
import { watch, computed } from 'vue';
import { AffiliateIcon, CheckCircleIcon, CheckIcon, InfoIcon, NotificationIcon, PersonIcon } from '@adersolutions/icons';
import { ItemImage, ReservationLocateDetail } from '@common/components';
import { ProposalStatusEnum } from '@common/enums';
import { useQuery } from '@shared/hooks';
import { CalendarIcon, ClockIcon } from '@adersolutions/icons';
import { Card, Dialog, Spinner, Button, Drawer, Thumb } from '@shared/components';
import { dateFormat, getFilePath, getName } from '@shared/utils';
import { routes } from '@espace-student/routes';
import { useMutation } from '@shared/hooks';
import { ProposalType, ReservationType } from '@common/types';
import { SizeEnum } from '@shared/enums';
const emit = defineEmits(['refetch', 'close']);
type PropsType = {
    item: ProposalType | null;
};
const props = defineProps<PropsType>();

const offersQuery = useQuery({ transformable: true });
const form = useMutation({
    status: props.item?.status || null,
    offer_id: null,
});
const reservation = computed(() => (props.item?.reservation || {}) as ReservationType);

watch(props, ({ item }) => {
    form.status = item?.status || null;
    form.offer_id = null;
    if (!offersQuery.data.length || item) {
        offersQuery.fetch(route(routes.api.balances.getBalanceByStudent, item?.student?.id || '-'));
    }
});

const onSubmit = (status: ProposalStatusEnum) => {
    form.status = status;
    props.item &&
        form.post(route(routes.api.notifications.proposals.update, props.item.id)).then(() => {
            emit('refetch');
            emit('close');
        });
};
</script>

<template>
    <Drawer :show="!!item" title="Détails de la séance" @close="$emit('close')">
        <Card v-if="item" class="px-3 flex-1">
            <h4 class="flex-center gap-2 mb-1 w-fit text-base font-semibold">
                <AffiliateIcon class="w-7 h-7 bg-gray-200 p-1 rounded-lg" />
                Une nouvelle séance a été proposée
            </h4>
            <ul class="py-2 list-disc list-outside pl-6 text-md bg-slate-200 my-2 rounded-lg">
                <li v-if="item?.comment">
                    {{ item?.comment }}
                </li>
                <li>Fais-moi savoir si tu as besoin de modifications ou de détails supplémentaires ! 🚗💨</li>
            </ul>

            <div class="text-dark border-t py-4 text-md overflow-clip relative">
                <div class="rainbow absolute -top-0.5 -left-20"></div>
                <dl class="grid grid-cols-3 py-1">
                    <dd class="opacity-70 flex gap-2 items-center">
                        <CalendarIcon class="w-4" />
                        Date
                    </dd>
                    <dd class="col-span-2 font-semibold">
                        <span class="mr-2">:</span>

                        {{ dateFormat(reservation.date, 'full') }}
                    </dd>
                </dl>
                <dl class="grid grid-cols-3 py-1">
                    <dd class="opacity-70 flex gap-2 items-center">
                        <ClockIcon class="w-4" />
                        Horaires
                    </dd>
                    <dd class="col-span-2 font-semibold">
                        <span class="mr-2">:</span>

                        {{ reservation.start_at }} à
                        {{ reservation.end_at }}
                    </dd>
                </dl>
                <dl v-if="reservation.training" class="grid grid-cols-3 py-1">
                    <dd class="opacity-70 flex gap-2 items-center">
                        <PersonIcon class="w-4" />
                        Instracteur
                    </dd>
                    <dd class="col-span-2 font-semibold">
                        <span class="mr-2">:</span>
                        {{ getName(reservation.monitor?.user) }}
                    </dd>
                </dl>
                <dl class="grid grid-cols-3 py-1">
                    <dd class="opacity-70 flex gap-2 items-center">
                        <NotificationIcon class="w-4" />
                        Rappel
                    </dd>
                    <dd class="col-span-2 font-semibold">
                        <span class="mr-2">:</span>
                        {{ dateFormat(`${reservation.date} ${reservation.start_at}`, 'fromNow') }}
                    </dd>
                </dl>
            </div>
            <ReservationLocateDetail v-if="reservation.lieu" :lieu="reservation.lieu" />

            <ul v-if="item" class="flex flex-col pb-10">
                <li>
                    <h4 class="font-semibold text-gray-800 text-sm mb-1">List des Produits</h4>
                    <Card v-if="offersQuery.fetching" block padding="lg">
                        <Spinner class="w-6 h-6 mx-auto" />
                    </Card>
                    <Card v-else block class="!mb-0 divide-y" :separated="false" as="ul">
                        <li
                            v-for="pr in offersQuery.data"
                            :key="pr.id"
                            :class="[
                                'p-2 t-3 btn-m flex justify-between items-center',
                                pr.offer_id === form.offer_id ? 'text-primary bg-primary/5 rounded-xl' : '',
                                pr.balance === 0 ? 'opacity-70 pointer-events-none' : '',
                            ]"
                            @click="form.offer_id = pr.offer_id"
                        >
                            <div class="flex gap-2 items-center">
                                <Thumb :src="getFilePath(pr.offer, true)" :size="SizeEnum.XS" />
                                <div class="flex-1 text-sm">
                                    <p>{{ pr.offer?.name }}</p>
                                    <p class="text-2xs text-gray-500">Balance: {{ pr.balance }}h</p>
                                </div>
                            </div>
                            <small v-if="pr.balance === 0">Indisponible</small>
                            <CheckIcon v-else-if="pr.offer_id === form.offer_id" class="w-7 h-7 text-primary" />
                        </li>
                    </Card>
                    <p class="text-2xs text-gray-500 mt-1 flex gap-2">
                        <InfoIcon class="w-4 h-4 inline-block" />
                        <span> Vouz devez choisir le produit pour confirmer la réservation de séance</span>
                    </p>
                </li>
            </ul>
        </Card>
        <div class="page-actions">
            <Button
                class="!w-1/3"
                full
                variant="danger"
                submit
                :loading="form.mutating && form.status === ProposalStatusEnum.CANCELLED"
                @click="onSubmit(ProposalStatusEnum.CANCELLED)"
            >
                Refusé
            </Button>
            <Button
                class="!w-2/3"
                full
                variant="info"
                :loading="form.mutating && form.status === ProposalStatusEnum.RESERVED"
                :disabled="!form.offer_id"
                @click="onSubmit(ProposalStatusEnum.RESERVED)"
            >
                Accepter
            </Button>
        </div>
    </Drawer>
</template>
