<script setup lang="ts">
import { computed, reactive, watch } from 'vue';
import { CancelStatus } from '@common/enums';
import { AlertDiamondIcon } from '@adersolutions/icons';
import { getFilePath, getName, isOutdated, dateFormat } from '@shared/utils';
import { Thumb, Spinner } from '@shared/components';
import { SizeEnum } from '@shared/enums';
import { ReservationType } from '@common/types';

const emit = defineEmits(['select']);

type PropsType = {
    item: ReservationType;
    places?: string;
    weeklyReservations?: ReservationType[];
    prevWeeklyReservations?: ReservationType[];
    prevPrevWeeklyReservations?: ReservationType[];
};

const props = withDefaults(defineProps<PropsType>(), {
    weeklyReservations: () => [],
    prevWeeklyReservations: () => [],
    prevPrevWeeklyReservations: () => [],
});

/* ---------------- CANCELLATION ---------------- */

const cancellation = computed(() => {
    if (!props.item.training) return null;
    return props.item?.training?.cancellation || null;
});

/* ---------------- HOURS CALCULATION ---------------- */

const calculateWeekHours = (reservations: ReservationType[]) => {
    return reservations.reduce((total, reservation) => {
        if (reservation.training && reservation.start_at && reservation.end_at) {
            const startParts = reservation.start_at.split(':');
            const endParts = reservation.end_at.split(':');

            const startHours =
                parseInt(startParts[0]) + parseInt(startParts[1]) / 60;
            const endHours =
                parseInt(endParts[0]) + parseInt(endParts[1]) / 60;

            const hours = endHours - startHours;
            return total + (hours > 0 ? hours : 0);
        }
        return total;
    }, 0);
};

const weeklyHoursReserved = computed(() =>
    calculateWeekHours(props.weeklyReservations)
);

const previousWeekHoursReserved = computed(() =>
    calculateWeekHours(props.prevWeeklyReservations || [])
);

const previousPrevWeekHoursReserved = computed(() =>
    calculateWeekHours(props.prevPrevWeeklyReservations || [])
);

/* ---------------- BLOCKING RULES ---------------- */

// (consecutive-weeks check removed)

// (weekly limit check removed for students)

// 🚫 Block today or tomorrow
const isInCurrentOrNextDay = computed(() => {
    if (!props.item.date) return false;

    const reservationDate = new Date(props.item.date);
    const today = new Date();
    const tomorrow = new Date(today);

    tomorrow.setDate(tomorrow.getDate() + 1);

    reservationDate.setHours(0, 0, 0, 0);
    today.setHours(0, 0, 0, 0);
    tomorrow.setHours(0, 0, 0, 0);

    return reservationDate <= tomorrow;
});

/* ---------------- HANDLE SELECT ---------------- */

const handleReservationSelect = () => {
    // 1️⃣ Today / Tomorrow block
    if (isInCurrentOrNextDay.value) {
        const reservationDate = new Date(props.item.date);
        const today = new Date();
        const earliestDate = new Date(today);
        earliestDate.setDate(earliestDate.getDate() + 2);

        const formatDate = (date: Date) => {
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const year = date.getFullYear();
            return `${day}/${month}/${year}`;
        };

        alert(
            `Vous ne pouvez pas réserver pour le ${formatDate(reservationDate)}.\nRéservations possibles à partir du ${formatDate(earliestDate)}`
        );
        return;
    }

    // (consecutive-weeks runtime check removed)

    // When reserving an available slot, ensure the student's weekly total
    // plus this slot's duration does not exceed 2 hours.
    if (props.item?.start_at && props.item?.end_at) {
        const startParts = props.item.start_at.split(':');
        const endParts = props.item.end_at.split(':');

        const startHours = parseInt(startParts[0]) + parseInt(startParts[1]) / 60;
        const endHours = parseInt(endParts[0]) + parseInt(endParts[1]) / 60;

        const slotHours = Math.max(0, endHours - startHours);

        if (weeklyHoursReserved.value + slotHours > 2) {
            alert(
                `Vous avez dépasser la limite des 2h par semaines pour plus d’informations contacter le secrétariat`
            );
            return;
        }
    }

    // 🚫 Block if student has already reserved for 2 weeks and trying to reserve for a 3rd week
    const weeksWithReservations = [
        props.weeklyReservations.length > 0,
        props.prevWeeklyReservations.length > 0,
        props.prevPrevWeeklyReservations.length > 0
    ].filter(Boolean).length;

    if (weeksWithReservations >= 2 && props.weeklyReservations.length === 0) {
        alert("Réservation possible que sur deux semaines");
        return;
    }

    emit('select', props.item);
};

/* ---------------- COMMENTS ---------------- */

const stateLocal = reactive({
    reservationComments: [] as any[],
    loadingComments: false,
});

const fetchReservationComments = async () => {
    if (!props.item?.id) return;

    stateLocal.loadingComments = true;
    try {
        const response = await fetch(
            `/reservations/${props.item.id}/comments`
        );
        const data = await response.json();
        stateLocal.reservationComments = data.data || [];
    } catch (error) {
        console.error('Error fetching comments:', error);
        stateLocal.reservationComments = [];
    } finally {
        stateLocal.loadingComments = false;
    }
};

watch(
    () => props.item?.id,
    (newId) => {
        if (newId) {
            fetchReservationComments();
        } else {
            stateLocal.reservationComments = [];
        }
    },
    { immediate: true }
);
</script>


<template>
    <div
        v-if="!props.item.training && (!places || parseInt(places) > 0)"
        :class="[
            'h-full rounded-lg flex-center flex-col text-xs p-1 border border-dashed border-green-500 bg-green-50 drop-shadow-sm text-center cursor-pointer',
        ]"
        @click="handleReservationSelect()"
    >
        Réservation possible à
        <br />
        <b> {{ item.lieu?.zone?.name }}, {{ places || item.lieu?.name }} </b>
    </div>

    <div
        v-else-if="item"
        :class="[
            'h-full flex flex-col box btn-m bg-rainbow',
            isOutdated({ date: item.datef, end_at: item.end_at }) ? 'bg-gray-100 text-gray-500' : 'bg-white',
        ]"
        @click="$emit('select', item)"
    >
        <span
            v-if="cancellation"
            :class="['px-3 py-px text-2xs w-full !border-none', cancellation ? 'status-' + CancelStatus[cancellation.status]?.class : '']"
        >
            <AlertDiamondIcon class="w-4 inline-block" /> {{ CancelStatus[cancellation.status]?.desc }}
        </span>
        <span v-else-if="isOutdated({ date: item.datef, end_at: item.end_at })" class="bg-gray-300 px-3 py-px text-2xs">
            La séance a eu lieu et est désormais fermée.
        </span>

        <div class="flex max-md:items-center md:flex-col-reverse max-md:gap-2 flex-1 px-2 py-1">
            <Thumb :src="getFilePath(item.monitor?.user)" :size="SizeEnum.MD" class="bg-white" />
            <div class="t-3 flex-1 text-xs">
                Une séance avec
                <b>{{ getName(item.monitor?.user) }}</b> à
                <p>{{ item.lieu?.name }}, {{ item.lieu?.zone?.name }}</p>
            </div>
        </div>

        <!-- COMMENTS SECTION (Same as Admin) -->
        <div v-if="stateLocal.reservationComments.length > 0" class="mt-2 px-2">
            <div v-if="stateLocal.loadingComments" class="text-center py-2">
                <Spinner class="w-4 h-4 mx-auto" />
                <p class="text-xs text-gray-500 mt-1">Chargement des commentaires...</p>
            </div>
            <div v-else class="space-y-2">
                <div v-for="(comment, index) in stateLocal.reservationComments" :key="comment.id" class="border-b pb-2 last:border-b-0">
                    <div class="flex justify-between items-start mb-1">
                        <span class="text-[8px] text-gray-400">
                            {{ dateFormat(comment.created_at, 'short') }}
                        </span>
                    </div>
                    <p class="text-[8px] text-gray-600 whitespace-pre-wrap bg-gray-50 p-2 rounded">
                        {{ comment.comment }}
                    </p>
                </div>
            </div>
        </div>

        <div class="rainbow"></div>
    </div>
</template>

