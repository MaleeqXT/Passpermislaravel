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
    date: item?.date || null,
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
            form.setData(bindData(reserv));
            form.key = performance.now();
            form.defaults();
            if (!form.color || form.color === '') {
                form.color = getReservationColor({
                    student_type: (reserv as any).student_type || (reserv as any).training?.student?.type || null,
                    package_type: (reserv as any).package_type || (reserv as any).training?.offer?.package_type || (reserv as any).training?.offer?.type_offre || null,
                    evaluation_type: (reserv as any).evaluation_type || (reserv as any).training?.offer?.evaluation_type || null,
                    is_cpf: (reserv as any).is_cpf || (reserv as any).training?.offer?.is_cpf || false,
                    offer: (reserv as any).training?.offer || (reserv as any).offer || null,
                });
            }
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

watch([() => form?.start_at, () => form?.end_at], ([start, end]) => {
    if (start && end) {
        form.hour = moment(end, 'HH:mm').diff(moment(start, 'HH:mm'), 'hour');
    } else {
        form.hour = 1;
    }
});

function getReservationColor(data: any): string {
    if (data.student_type === 'manual') return '#00FF00';
    if (data.student_type === 'automatic') return '#FFFF00';
    if (data.package_type === 'ppt_automatic') return '#FFD6E0';
    if (data.package_type === 'ppt_manual') return '#FF0000';
    if (data.package_type === 'cpf' || data.is_cpf === true || data.offer?.is_cpf === true) return '#FFA500';
    if (data.evaluation_type === 'manual_first_hour' || data.offer?.evaluation_type === 'manual_first_hour') return '#FFB6C1';
    if (data.evaluation_type === 'automatic_first_hour' || data.offer?.evaluation_type === 'automatic_first_hour') return '#800080';
    if (data.offer) {
        const offer = data.offer;
        if (offer.package_type === 'ppt_automatic') return '#FFD6E0';
        if (offer.package_type === 'ppt_manual') return '#FF0000';
        if (offer.is_cpf) return '#FFA500';
        if (offer.evaluation_type === 'manual_first_hour') return '#FFB6C1';
        if (offer.evaluation_type === 'automatic_first_hour') return '#800080';
    }
    return '#F3F4F6';
}

const onSubmit = async () => {
    try {
        // keep existing properties removal as-is (the project's helpers expect these)
        // TS may warn about delete, but runtime works; we focus on import/runtime fix here.
        // eslint-disable-next-line @typescript-eslint/ban-ts-comment
        // @ts-ignore
        delete form.all;
        // @ts-ignore
        delete form.key;
        if (isEdit.value) {
    await form.put(
        route(routes.api.reservation_unrestricted.update, state.selected?.id)
        );
        } else {
            // @ts-ignore
            delete form.reservation_id;
        await form.post(route(routes.api.reservation_unrestricted.store));
        }
        emit('refetch');
        close();
    } catch (error) {
        console.warn(error);
    }
};


const onAreaChange = ({ place, area }: any = {}) => {
    form.lieu_id = place?.id || null;
    locations.area = area;
    locations.place = place;
};

const onOffreChange = (item: WalletOffreType) => {
    form.offer_id = item.offer_id;
    const offer = (item as any).offer || {};
    if (offer.color) {
        form.color = offer.color;
        return;
    }
    if (offer.is_cpf) {
        form.color = '#FFA500';
        return;
    }
    if (offer.is_auto) {
        form.color = '#FFFF00';
        return;
    }
    form.color = getReservationColor({
        student_type: (item as any).student_type || null,
        package_type: (offer as any).package_type || (offer.type_offre || null),
        evaluation_type: (offer as any).evaluation_type || null,
    });
};
</script>

<template>
    <Drawer :show="state.show" width="md" :title="isEdit ? 'Modifier la réservation' : 'Nouvelle réservation (Sans restriction)'" @close="close">
        <form :key="form.key" class="flex-1 flex flex-col" @submit.prevent="onSubmit">
            <div class="px-3 border-y bg-white border-y-slate-300 flex flex-col gap-5 flex-1 py-5">
                <Switch :model-value="form.is_active === 1 ? true : false" label="Activer" @change="form.is_active = $event ? 1 : 2" />
                <DateField v-model="form.date" :min-date="moment().toDate()" label="Date Lesson" />

                <div class="flex gap-3">
                    <DateField
                        v-model="form.start_at"
                        :error="form.errors.start_at as string | string[] | undefined"
                        time
                        min-time="07:00"
                        max-time="23:00"
                        label="Heure de début"
                    />
                    <DateField
                        v-model="form.end_at"
                        :error="form.errors.end_at as string | string[] | undefined"
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
                    :errors="form.errors.monitor_id as string | string[] | undefined"
                />

                <ListStudentsDialog
                    form
                    :model-value="state.selected?.training?.student?.user"
                    :disabled="!form.monitor_id"
                    @change="form.student_id = $event?.id || null"
                    :errors="form.errors.student_id as string | string[] | undefined"
                />

                <ListOffersDialog
                    form
                    :errors="form.errors.offer_id as string | string[] | undefined"

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
                <Errors v-if="(form.errors as any).message" :errors="(form.errors as any).message" />
            </div>

            <div class="flex flex-row justify-between p-4 bg-gray-100 text-right rounded-b-2xl sticky bottom-0">
                <Button variant="secondary" @click="close">Cancel</Button>
                <Button variant="primary" submit :loading="form.mutating">{{ isEdit ? 'Modifier' : 'Enregistrer' }}</Button>
            </div>
        </form>
    </Drawer>
</template>
