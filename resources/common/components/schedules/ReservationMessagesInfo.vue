<script setup lang="ts">
import { isOutdated } from '@shared/utils';
import { computed } from 'vue';
import { CalendarCheckIcon, CalendarTimeIcon, XCircleIcon } from '@adersolutions/icons';
import { CancelStatus, CancelStatusEnum } from '@common/enums';
import type { ReservationType } from '@common/types';

const emit = defineEmits(['close', 'refresh']);
type PropsType = {
    item: ReservationType | null;
    isStudent?: boolean;
    isAvailability?: boolean;
};

const props = defineProps<PropsType>();
const cancellation = computed(() => {
    const status = props.item?.training?.cancellation?.status;
    return status !== undefined ? CancelStatus[status] : null;
});
</script>

<template>
    <div v-if="item" class="contents">
        <h4 class="flex-center gap-2 w-fit text-lg font-semibold">
            <XCircleIcon
                v-if="cancellation?.id === CancelStatusEnum.SUCCESS_CANCELED"
                class="w-7 h-7 bg-red-500 text-white p-1 rounded-lg"
            />
            <CalendarCheckIcon v-else-if="item.review_monitor" class="w-7 h-7 bg-green-500 text-white p-1 rounded-lg" />
            <CalendarTimeIcon v-else class="w-7 h-7 bg-gray-200 p-1 rounded-lg" />
            Séance {{ cancellation?.id === CancelStatusEnum.SUCCESS_CANCELED ? 'Annulé' : 'details' }}
        </h4>
        <div v-if="isAvailability" class="py-4 text-md">Aucune séance n'est disponible pour le moment.</div>
        <ul v-else class="py-4 list-disc list-outside pl-4 text-md">
            <li v-if="isOutdated(item)">La date de séance est passée</li>
            <template v-else-if="item.training">
                <li>La date de séance n'est pas encore arrivée.</li>
                <li v-if="isStudent">
                    Veuillez arriver 10 minutes avant le début de la séance avec votre permis ou justificatif d'inscription.
                </li>
            </template>
            <li v-if="!item.training && isStudent">
                Vérifiez votre disponibilité et vos documents avant de réserver. Des frais peuvent s'appliquer en cas d'annulation tardive.
                Bonne conduite ! 🚗
            </li>
        </ul>
        <dl v-if="cancellation" :class="['text-xs/5 box mb-3', 'status-' + cancellation.class]">
            <dt class="font-semibold bg-dark/10 rounded-t-lg px-2">
                {{ cancellation.desc }}
            </dt>
            <div class="h-rainbow"></div>
            <dd class="px-3 py-1">
                {{ item?.training?.cancellation?.comment }}
            </dd>
        </dl>
    </div>
</template>
