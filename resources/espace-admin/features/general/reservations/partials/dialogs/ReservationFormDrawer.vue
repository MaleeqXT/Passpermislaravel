<script setup lang="ts">
import { Button, ColorField, DateField, Drawer, Errors, Switch } from '@shared/components';
import { AreaSelectionDialog, ListMonitorsDialog, ListOffersDialog, ListStudentsDialog } from '@common/components';
import { computed, watch, reactive } from 'vue';
import { routes } from '@espace-admin/routes';
import { useMutation } from '@shared/hooks';
import moment from 'moment-timezone';
import type { PlaceType, WalletOffreType, AreaType, ReservationType } from '@common/types';
import { useReservations } from '../../ReservationsPage';

type StateType = {
    area: AreaType | null;
    place: PlaceType | null;
};

const emit = defineEmits(['refetch']);
const { state } = useReservations();

const locations = reactive<StateType>({
    area: null,
    place: null,
});

const bindData = (item?: Partial<ReservationType> | null) => ({
    reservation_id: item?.id || null,
    date: item?.date ? moment(item.date).toDate() : moment().toDate(),
    start_at: item?.start_at || null,
    end_at: item?.end_at || null,
    is_active: item?.is_active || 1,
    hour: item?.hour || 1,
    monitor_id: item?.monitor?.id || null,
    student_id: item?.training?.student?.id || null,
    offer_id: item?.training?.offer_id || null,
    lieu_id: item?.lieu_id || null,
    color: item?.color || '',
    all: true,
    key: performance.now(),
});

const form = useMutation(bindData());
const isEdit = computed(() => !!state.selected?.id);

const areaPlace = computed(() => {
    const res = JSON.parse(JSON.stringify(state.selected?.lieu || {}));
    if (res.zone) {
        return {
            area: res.zone,
            place: res,
        };
    }
    return {
        area: null,
        place: null,
    };
});

watch(
    () => state.selected,
    (reserv: Partial<ReservationType> | null) => {
        if (reserv) {
            console.log('bindData(reserv)', reserv, bindData(reserv));
            form.setData(bindData(reserv));
            form.key = performance.now();
            // form.defaults();
            // Compute a deterministic color for the reservation using client rules.
            // Prefer the offer-provided color when available, otherwise compute using priority:
            // evaluation (first hour) -> CPF -> PPT package -> student type -> fallback.
            const computedColor = getReservationColor({
                student_type: (reserv as any).student_type || (reserv as any).training?.student?.type || null,
                package_type: (reserv as any).package_type || (reserv as any).training?.offer?.package_type || (reserv as any).training?.offer?.type_offre || null,
                evaluation_type: (reserv as any).evaluation_type || (reserv as any).training?.offer?.evaluation_type || null,
                is_cpf: (reserv as any).is_cpf || (reserv as any).training?.offer?.is_cpf || false,
                offer: (reserv as any).training?.offer || (reserv as any).offer || null,
            });

            // If the offer contains an explicit color, prefer it. Otherwise use the computed color.
            form.color = ((reserv as any).training?.offer?.color || (reserv as any).offer?.color) || computedColor;
            if (reserv?.lieu?.zone && isEdit) {
                const { lieu } = reserv;
                locations.place = lieu;
                locations.area = lieu?.zone || null;
                form.lieu_id = reserv.lieu.id;
            }
        }
    }
);

const close = () => {
    state.show = false;
    state.selected = null;
};

// ⏰ Automatically calculate lesson hours
watch([() => form?.start_at, () => form?.end_at], ([start, end]) => {
    if (start && end) {
        form.hour = moment(end, 'HH:mm').diff(moment(start, 'HH:mm'), 'hour');
    } else {
        form.hour = 1;
    }
});

// ✅ Color logic based on client rules (deterministic priority)
function getReservationColor(data: any): string {
    // Priority (most specific -> least specific):
    // 1) evaluation (manual/automatic first hour)
    if (data.evaluation_type === 'manual_first_hour' || data.offer?.evaluation_type === 'manual_first_hour') return '#FFB6C1'; // Light pink
    if (data.evaluation_type === 'automatic_first_hour' || data.offer?.evaluation_type === 'automatic_first_hour') return '#800080'; // Purple

    // 2) CPF payment
    if (data.package_type === 'cpf' || data.is_cpf === true || data.offer?.is_cpf === true) return '#FFA500'; // Orange

    // 3) PPT packages
    if (data.package_type === 'ppt_automatic' || data.offer?.package_type === 'ppt_automatic') return '#FFD6E0'; // Very light pink
    if (data.package_type === 'ppt_manual' || data.offer?.package_type === 'ppt_manual') return '#FF0000'; // Red

    // 4) student type
    if (data.student_type === 'manual') return '#28A745'; // Green (softer)
    if (data.student_type === 'automatic') return '#FFD700'; // Yellow (gold)

    // 5) Offer-level fallbacks (if any remaining flags)
    if (data.offer) {
        const offer = data.offer;
        if (offer.evaluation_type === 'manual_first_hour') return '#FFB6C1';
        if (offer.evaluation_type === 'automatic_first_hour') return '#800080';
        if (offer.is_cpf) return '#FFA500';
        if (offer.package_type === 'ppt_automatic') return '#FFD6E0';
        if (offer.package_type === 'ppt_manual') return '#FF0000';
    }

    // Default fallback -> light gray to avoid white-on-white UI issues
    return '#F3F4F6';
}

// Optimize the onSubmit function for updates
const onSubmit = async () => {
    try {
        delete form.all;
        delete form.key;
        delete form.reservation_id; // Ensure unnecessary fields are removed

        // Recalculate hour from start_at and end_at to ensure accuracy
        if (form.start_at && form.end_at) {
            form.hour = moment(form.end_at, 'HH:mm').diff(moment(form.start_at, 'HH:mm'), 'hour');
        }

        // Convert date to string format if it's a Date object
        if (form.date instanceof Date) {
            form.date = moment(form.date).format('YYYY-MM-DD');
        }

        // Optimize API call for updates
        const apiCall = isEdit.value
            ? form.put(route(routes.api.reservations.update, state.selected?.id), {
                  // Send only the fields that have changed
                  date: form.date,
                  start_at: form.start_at,
                  end_at: form.end_at,
                  is_active: form.is_active,
                  hour: form.hour,
                  monitor_id: form.monitor_id,
                  lieu_id: form.lieu_id,
                  color: form.color,
              })
            : form.post(route(routes.api.reservation.store));

        // Show loading indicator during the API call
        form.mutating = true;
        await apiCall;
        form.mutating = false;

        emit('refetch');
        close();
    } catch (error: any) {
        form.mutating = false;
        console.warn(error);
        // Handle conflict/reservation errors
        if (error?.response?.status === 422) {
            const message = error?.response?.data?.message || error?.message;
            if (message) {
                form.errors.message = [message];
            }
        } else if (error?.message) {
            form.errors.message = [error.message];
        }
    }
};

const onAreaChange = ({ place, area }: any = {}) => {
    form.lieu_id = place?.id || null;
    locations.area = area;
    locations.place = place;
};

// ✅ Updated Offer Change Logic (color mapping added)
const onOffreChange = (item: WalletOffreType) => {
    form.offer_id = item.offer_id;
    // Prefer server-provided color on the offer when available
    const offer = (item as any).offer || {};

    // Prefer an explicit offer color when present; otherwise compute deterministically
    form.color = (offer.color) || getReservationColor({
        student_type: (item as any).student_type || null,
        package_type: (offer as any).package_type || (offer.type_offre || null),
        evaluation_type: (offer as any).evaluation_type || null,
        is_cpf: (offer as any).is_cpf || false,
        offer: offer || null,
    });
};
</script>

<template>
    <Drawer :show="state.show" width="md" :title="isEdit ? 'Modifier la réservation' : 'Nouvelle réservation'" @close="close">
        <form :key="form.key" class="flex-1 flex flex-col" @submit.prevent="onSubmit">
            <div class="px-3 border-y bg-white border-y-slate-300 flex flex-col gap-5 flex-1 py-5">
                <Switch :model-value="form.is_active === 1 ? true : false" label="Activer" @change="form.is_active = $event ? 1 : 2" />
                <DateField v-model="form.date" :min-date="moment().toDate()" label="Date Lesson" />

                <div class="flex gap-3">
                    <DateField
                        v-model="form.start_at"
                        :error="form.errors.start_at"
                        time
                        min-time="07:00"
                        max-time="23:00"
                        label="Heure de début"
                    />
                    <DateField
                        v-model="form.end_at"
                        :error="form.errors.end_at"
                        time
                        :disabled="!form.start_at"
                        :min-time="moment(form.start_at, 'HH:mm').add(1, 'hour').format('HH:mm')"
                        :max-time="'23:00'"
                        label="Heure de fin"
                        placeholder="Sélectionner l'heure"
                    />
                </div>

                <AreaSelectionDialog form @change="onAreaChange" :model-value="areaPlace" />

                <ListMonitorsDialog
                    form
                    :filters="{ zone_id: locations.area?.id }"
                    :model-value="state.selected?.monitor?.user"
                    @change="form.monitor_id = $event?.id || null"
                    :errors="form.errors.monitor_id"
                />

                <ListStudentsDialog
                    form
                    :model-value="state.selected?.training?.student?.user"
                    :disabled="!form.monitor_id"
                    @change="form.student_id = $event?.id || null"
                    :errors="form.errors.student_id"
                />

                <ListOffersDialog
                    form
                    :errors="form.errors.offer_id"
                    :filters="{ student_id: form.student_id }"
                    :model-value="
                        state.selected?.training?.offer
                            ? {
                                  offer_id: state.selected?.training?.offer_id,
                                  offer: state.selected?.training?.offer,
                                  name: state.selected?.training?.offer?.name,
                              }
                            : undefined
                    "
                    :disabled="!form.student_id"
                    @change="onOffreChange"
                />

                <ColorField v-model="form.color" label="Couleur de la Réservation" class="flex-none" />
                <Errors v-if="form.errors.message" :errors="form.errors.message" />
            </div>

            <div class="flex flex-row justify-between p-4 bg-gray-100 text-right rounded-b-2xl sticky bottom-0">
                <Button variant="secondary" @click="close">Cancel</Button>
                <Button variant="primary" submit :loading="form.mutating">{{ isEdit ? 'Modifier' : 'Enregistrer' }}</Button>
            </div>
        </form>
    </Drawer>
</template>
