<script setup lang="ts">
import { Card, Button, DialogConfirm, Errors, InputField, Drawer, EmptyState, Spinner, RadioField } from '@shared/components';
import moment from 'moment-timezone';
import { dateFormat, getName, isOutdated } from '@shared/utils';
import { computed, watch, ref, reactive } from 'vue';
import { ItemImage, ReservationLocateDetail, ReservationMessagesInfo } from '@common/components';
import { getFilePath } from '@shared/utils';
import { useForm } from '@inertiajs/vue3';
import { routes } from '@espace-student/routes';
import { CalendarIcon, ClockIcon, NotificationIcon, PersonIcon, AlertDiamondIcon } from '@adersolutions/icons';
import { CancelStatus } from '@common/enums';
import { useStudentSpace } from '@espace-student/stores';
import { useQuery } from '@shared/hooks';
import { useCart } from '@shared/stores';
import type { ReservationType } from '@common/types';
import DAFFDownload from '@espace-student/features/cpf/partials/daff/DAFFDownload.vue';

const emit = defineEmits(['close', 'refresh']);
type PropsType = {
    item: ReservationType | null;
};
type FormCancelType = {
    training_id: string | undefined;
    is_justified: boolean;
    comment: string;
};

type FormHourRequestType = {
    hours_requested: number;
    comment: string;
    reservation_id?: string;
};

type FormReserveType = {
    offer_id: string | null;
    date?: string | null;
    start_at?: string | null;
    end_at?: string | null;
    hour?: number;
    lieux?: string[];
};

const props = defineProps<PropsType>();
const { locations, user } = useStudentSpace();

const formCancel = useForm<FormCancelType>({
    training_id: undefined,
    is_justified: false,
    comment: '',
});

const formHourRequest = useForm<FormHourRequestType>({
    hours_requested: 1,
    comment: '',
    reservation_id: undefined,
});

const formReserve = useForm<FormReserveType>({
    offer_id: null,
});

const cancellation = computed(() => props.item?.training?.cancellation);
const reservationHours = computed(() => {
    const directHour = Number(props.item?.hour ?? props.item?.training?.reservation?.hour ?? 0);
    return directHour > 0 ? directHour : 1;
});

// dialogs
const showCancelDialog = ref(false); // for "Annuler 1h"

// disable hour‑adjustment requests if the reservation is today or tomorrow
const canRequestHour = computed(() => {
    if (!props.item?.date) return false;
    const now = moment().startOf('day');
    const itemDate = moment(props.item.date).startOf('day');
    // allow only if reservation date is at least two full days ahead (third day or later)
    return itemDate.isAfter(now.add(1, 'day'));
});

const onHourButtonClick = () => {
    if (!canRequestHour.value) {
        alert('Vous ne pouvez pas annuler une heure pour aujourd\'hui ou demain.');
        return;
    }
    showCancelDialog.value = true;
};

const offresQuery = useQuery(
    {
        url: route(routes.api.balances.getBalanceByStudent, user.student?.id || '-'),
        transformable: true,
        callback: (data = []) => {
            // Backend now returns separated balances from sales query
            // Just format and log for display
            return data.map((wallet: any) => {
                const offer = wallet.offer || wallet;
                const oneTimeBalance = Number(wallet.one_time_balance || 0);
                const installmentTotalBalance = Number(wallet.installment_total_balance || 0);
                const perInstallmentBalance = Number(wallet.per_installment_balance || 0);
                const totalBalance = Number(wallet.total_balance || 0);
                const installmentCount = Number(wallet.installments || wallet.multi_payment || 1);

                // Log breakdown for debugging
                if (oneTimeBalance > 0 && installmentTotalBalance > 0) {
                    console.log(`💳 ${offer.name}: One-time: ${oneTimeBalance}h + Installment: ${installmentTotalBalance}h (${perInstallmentBalance}h per ${installmentCount} installments) = ${totalBalance}h total`);
                } else if (installmentTotalBalance > 0) {
                    console.log(`💳 ${offer.name}: Installment: ${installmentTotalBalance}h (${perInstallmentBalance}h per ${installmentCount} installments) = ${totalBalance}h total`);
                } else if (oneTimeBalance > 0) {
                    console.log(`💳 ${offer.name}: One-time: ${oneTimeBalance}h`);
                } else {
                    console.log(`💳 ${offer.name}: No balance`);
                }

                return {
                    ...wallet,
                    ...offer,
                    one_time_balance: oneTimeBalance,
                    installment_total_balance: installmentTotalBalance,
                    per_installment_balance: perInstallmentBalance,
                    total_balance: totalBalance,
                    installments: installmentCount,
                    disabled: totalBalance === 0 || totalBalance >= offer.total_hours
                };
            });
        },
    }
);


const onCancelConfirm = () => {
    formCancel.post(route(routes.cancellations.store), {
        onSuccess: () => {
            formCancel.reset();
            showCancelDialog.value = false;
            onClose();
            emit('refresh');
        },
    });
};

const onHourRequestConfirm = () => {
    // ensure reservation id is attached so server knows which slot to cancel
    formHourRequest.reservation_id = props.item?.id;
    formHourRequest.hours_requested = reservationHours.value;

    // send request to admin approvels endpoint directly via URL
  formHourRequest.post(route('student.approvels.store'), {
    onSuccess: () => {
        formHourRequest.reset();
        showCancelDialog.value = false;
        emit('refresh');
    },
});
};

const onClose = () => {
    emit('close');
};

watch(props, ({ item }) => {
    formReserve.offer_id = null;
    if (item && !item.training) {
        offresQuery.fetch();
    }
});

const onSubmitReserve = async () => {
    console.log('🔵 onSubmitReserve called');
    console.log('📦 props.item:', props.item);
    console.log('💰 Selected offer_id:', formReserve.offer_id);

    // Optimistic UI update: decrement displayed balance immediately
    const selectedId = String(formReserve.offer_id || '');
    let previousItem: any = null;
    try {
        const list = (offresQuery.data || []);
        const idx = list.findIndex((o: any) => String(o.id) === selectedId);
        if (idx !== -1) {
            previousItem = { ...(list[idx] || {}) } as any;
            const it = { ...(list[idx] || {}) } as any;
            it.total_balance = Math.max(0, Number(it.total_balance || 0) - 1);
            if (Number(it.one_time_balance || 0) > 0) {
                it.one_time_balance = Math.max(0, Number(it.one_time_balance) - 1);
            } else if (Number(it.installment_total_balance || 0) > 0) {
                it.installment_total_balance = Math.max(0, Number(it.installment_total_balance) - 1);
            }
            if (it.installments && Number(it.installments || 0) > 0) {
                it.per_installment_balance = Math.floor(Number(it.installment_total_balance || 0) / Number(it.installments || 1));
            }
            it.disabled = Number(it.total_balance || 0) <= 0;
            if (Array.isArray(offresQuery.data)) {
                offresQuery.data.splice(idx, 1, it);
            }
        }
    } catch (e) {
        console.debug('Could not apply optimistic balance update', e);
    }

    const formData = {
        offer_id: formReserve.offer_id,
        date: props.item?.datef,
        start_at: props.item?.start_at,
        end_at: props.item?.end_at,
        lieu_id: locations.place,
        monitor_id: props.item?.monitor_id,
        hour: props.item?.hour,
    };
    console.log('📤 Sending form data:', formData);

    formReserve
        .transform(d => ({
            ...d,
            date: props.item?.datef,
            start_at: props.item?.start_at,
            end_at: props.item?.end_at,
            lieu_id: locations.place,
            monitor_id: props.item?.monitor_id,
            hour: props.item?.hour,
        }))
        .post(route(routes.reservations.store), {
            preserveScroll: true,
            onSuccess: async (page) => {
                console.log('✅ Reservation SUCCESS!', page);
                // Fetch fresh balances immediately
                try {
                    await offresQuery.fetch();
                } catch (e) {
                    console.debug('Could not fetch balances after reservation', e);
                }

                // Wait a moment for data to update, then close
                setTimeout(() => {
                    emit('refresh');
                    onClose();
                }, 300);
            },
            onError: (errors: any) => {
                console.error('❌ Reservation ERROR:', errors);
                console.log('Full page response:', errors);
                // Revert optimistic change on error
                try {
                    if (previousItem) {
                        const list = (offresQuery.data || []);
                        const idx = list.findIndex((o: any) => String(o.id) === selectedId);
                        if (idx !== -1) {
                            if (Array.isArray(offresQuery.data)) {
                                offresQuery.data.splice(idx, 1, previousItem);
                            }
                        }
                    }
                } catch (e) {
                    console.debug('Could not revert optimistic balance update', e);
                }
            },
            onFinish: () => {
                console.log('🏁 Reservation request finished');
            },
        });
};


const timeLeftMessage = computed(() => {
    if (!props.item) return { hours: 0, message: '', className: '' };

    const sessionDateTime = moment(`${props.item.datef} ${props.item.start_at}`);
    const hoursLeft = sessionDateTime.diff(moment(), 'hours');

    if (hoursLeft > 48) {
        return {
            hours: hoursLeft,
            className: 'bg-yellow-100 text-yellow-900',
            message: 'que vous confirmez votre annulation au moins 48 heures avant la séance, vous serez remboursé intégralement.',
        };
    } else if (props.item.datef === dateFormat(undefined, 'iso')) {
        return {
            hours: hoursLeft,
            className: 'bg-red-50 text-red-600',
            message: "Cette séance ne peut pas être annulée car elle est prévue pour aujourd'hui.",
        };
    } else {
        return {
            hours: hoursLeft,
            className: 'bg-orange-50 text-orange-600',
            message: 'Le délai de 48 heures est dépassé. Votre annulation sera soumise à une revue pour Justifié votre absence.',
        };
    }
});

// ---------------- TRAINING PROPOSALS ----------------
const state = reactive({
    trainingProposals: [] as any[],
    loadingProposals: false,
});

const fetchTrainingProposals = async () => {
    if (!props.item?.id) return;

    state.loadingProposals = true;
    try {
        const response = await fetch(`/api/training-proposals?reservation_id=${props.item.id}`);
        const data = await response.json();
        state.trainingProposals = data.data || [];
    } catch (error) {
        console.error('Error fetching training proposals:', error);
        state.trainingProposals = [];
    } finally {
        state.loadingProposals = false;
    }
};

// ---------------- RESERVATION COMMENTS ----------------
const stateLocal = reactive({
    showCommentDrawer: false,
    reservationComments: [] as any[],
    loadingComments: false,
});

const fetchReservationComments = async () => {
    if (!props.item?.id) return;

    stateLocal.loadingComments = true;
    try {
        const response = await fetch(`/reservations/${props.item.id}/comments`);
        const data = await response.json();
        stateLocal.reservationComments = data.data || [];
    } catch (error) {
        console.error('Error fetching comments:', error);
        stateLocal.reservationComments = [];
    } finally {
        stateLocal.loadingComments = false;
    }
};

watch(() => props.item?.id, (newId) => {
    if (newId) {
        fetchTrainingProposals();
        fetchReservationComments();
    }
}, { immediate: true });

// helpers
const allComments = computed(() => {
    return [...(state.trainingProposals || []), ...(stateLocal.reservationComments || [])];
});
const latestComment = computed(() => {
    if (allComments.value.length === 0) return null;
    return [...allComments.value].sort(
        (a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime()
    )[0];
});

const showCommentDrawer = () => (stateLocal.showCommentDrawer = true);
const closeCommentDrawer = () => (stateLocal.showCommentDrawer = false);

// Helper: Get the correct balance display for an offer (accounting for installments)
const getOfferDisplayBalance = (offer: any) => {
    if (!offer) return 0;

    const fullBalance = Number(offer.balance || 0);

    // Try to get selected installment from cart store or sessionStorage
    let selectedInstallment = null;
    try {
        const cart = useCart?.() || {};
        const offerId = String(offer.id || '');
        selectedInstallment = cart.selectedInstallments?.[offerId];
    } catch (e) {
        // ignore
    }

    // Fallback to sessionStorage if not in reactive store
    if (!selectedInstallment) {
        try {
            const persisted = JSON.parse(sessionStorage.getItem('cart_selected_installments') || '{}');
            selectedInstallment = persisted[String(offer.id || '')];
        } catch (e) {
            selectedInstallment = null;
        }
    }

    // If installment selected, calculate balance per installment
    if (selectedInstallment?.priceType === 'installment') {
        let pricingList = offer.agency_pricing;
        if (typeof pricingList === 'string') {
            try { pricingList = JSON.parse(pricingList); } catch { pricingList = null; }
        }

        const installments = Array.isArray(pricingList) && pricingList.length
            ? (pricingList[0]?.installments || [])
            : (offer.installments || []);

        const count = installments.length || Number(offer.multi_payment || 1);

        if (count > 1 && fullBalance > 0) {
            const base = Math.floor(fullBalance / count);
            const remainder = fullBalance - (base * count);
            // If it's the first installment, add remainder
            if (selectedInstallment.installmentNo === 1 || selectedInstallment.installmentNo === (installments[0]?.no || 1)) {
                return base + remainder;
            }
            return base;
        }
    }


    return fullBalance;
};

        // Watch for the offer display balance reaching 1 and trigger a safe browser notification.
        const offerDisplayBalance = computed(() => getOfferDisplayBalance(props.item?.training?.offer));

        watch(
            () => offerDisplayBalance.value,
            (newVal, oldVal) => {
                try {
                    if (typeof window === 'undefined') return;
                    if (!('Notification' in window)) return;
                    console.debug('offerDisplayBalance changed', { newVal, oldVal, permission: (typeof Notification !== 'undefined' ? Notification.permission : 'no-notification') });
                    if (newVal === 1 && oldVal !== 1) {
                        if (Notification.permission === 'granted') {
                            new Notification('Rappel: 1h restante', {
                                body: "Il vous reste 1h. Pensez à effectuer le prochain paiement pour cette offre.",
                            });
                        } else if (Notification.permission !== 'denied') {
                            Notification.requestPermission().then((permission) => {
                                if (permission === 'granted') {
                                    new Notification('Rappel: 1h restante', {
                                        body: "Il vous reste 1h. Pensez à effectuer le prochain paiement pour cette offre.",
                                    });
                                }
                            });
                        }
                    }
                } catch (e) {
                    console.debug('Notification watcher error', e);
                }
            },
            { immediate: true }
        );
</script>

<template>
    <section class="contents">
        <Drawer :show="!!item" :title="item?.training ? 'Réservation' : 'Reserver une séance'" @close="onClose">


            <div v-if="item" class="flex flex-col h-full">
                <div class="p-3 flex-1">
                    <ReservationMessagesInfo is-student :item="item" />

                    <div class="text-dark border-t py-4 text-md overflow-clip relative">
                        <dl class="grid grid-cols-3 py-1">
                            <dd class="opacity-70 flex gap-2 items-center"><CalendarIcon class="w-4" /> Date</dd>
                            <dd class="col-span-2 font-semibold"><span class="mr-2">:</span> {{ dateFormat(item.date, 'full') }}</dd>
                        </dl>
                        <dl class="grid grid-cols-3 py-1">
                            <dd class="opacity-70 flex gap-2 items-center"><ClockIcon class="w-4" /> Heure</dd>
                            <dd class="col-span-2 font-semibold"><span class="mr-2">:</span> {{ item.start_at }} à {{ item.end_at }}</dd>
                        </dl>
                        <dl v-if="item.training" class="grid grid-cols-3 py-1">
                            <dd class="opacity-70 flex gap-2 items-center"><PersonIcon class="w-4" /> Instructeur</dd>
                            <dd class="col-span-2 font-semibold"><span class="mr-2">:</span> {{ getName(item?.monitor?.user) }}</dd>
                        </dl>
                        <dl class="grid grid-cols-3 py-1">
                            <dd class="opacity-70 flex gap-2 items-center"><NotificationIcon class="w-4" /> Rappel</dd>
                            <dd class="col-span-2 font-semibold"><span class="mr-2">:</span> {{ dateFormat(`${item.date} ${item.start_at}`, 'fromNow') }}</dd>
                        </dl>
                    </div>

                    <ReservationLocateDetail :lieu="item.lieu" />

                    <template v-if="item.training">
                        <h4 class="font-semibold text-gray-800 text-md mb-1">Offre</h4>
                        <Card block padding="sm">
                            <ItemImage
                                :src="getFilePath(item.training.offer, true)"
                                :title="item.training?.offer?.name"
                                :content="`Balance: ${getOfferDisplayBalance(item.training?.offer)}h`"
                            />
                            <!-- If only 1 hour remains, prompt the student to make the next payment -->
                            <div v-if="getOfferDisplayBalance(item.training?.offer) === 1" class="mt-2 p-2 bg-yellow-50 text-yellow-800 text-sm rounded">
                                Il vous reste 1h. Pensez à effectuer le prochain paiement pour cette offre.
                            </div>
                        </Card>

                        <!-- Comment Section -->
                        <Card title="Commentaires" class="mt-4">
                            <div class="p-3 bg-gray-50 rounded-lg">
                                <div v-if="state.loadingProposals || stateLocal.loadingComments" class="text-center py-4">
                                    <Spinner class="w-6 h-6 mx-auto" />
                                    <p class="text-sm text-gray-500 mt-2">Chargement des commentaires...</p>
                                </div>

                                <div v-if="item.training?.comment" class="mb-4">
                                    <p class="text-sm font-medium text-gray-700">Commentaire de formation:</p>
                                    <p class="text-sm text-gray-600 whitespace-pre-wrap mt-1 p-2 bg-white rounded border">
                                        {{ item.training.comment }}
                                    </p>
                                </div>

                                <div v-if="latestComment" class="mb-4">
                                    <p class="text-sm font-medium text-gray-700">Dernier commentaire:</p>
                                    <p class="text-sm text-gray-600 whitespace-pre-wrap mt-1 p-2 bg-white rounded border">
                                        {{ latestComment.comment }}
                                    </p>
                                    <p class="text-xs text-gray-400 mt-1">
                                        {{ dateFormat(latestComment.created_at, 'short') }}
                                    </p>
                                </div>

                                <div v-if="allComments.length > 0" class="mb-3">
                                    <p class="text-xs text-gray-500">
                                        {{ allComments.length }} commentaire(s)
                                    </p>
                                </div>

                                <div
                                    v-if="!state.loadingProposals && !stateLocal.loadingComments && allComments.length === 0 && !item.training?.comment"
                                >
                                    <p class="text-sm font-medium text-gray-700">Commentaires:</p>
                                    <span class="text-gray-400 font-light text-sm">Aucun commentaire</span>
                                </div>

                                <Button
                                    v-if="allComments.length > 0"
                                    variant="outline"
                                    size="sm"
                                    @click="showCommentDrawer"
                                    class="mt-2"
                                >
                                    Voir tous les commentaires ({{ allComments.length }})
                                </Button>
                            </div>
                        </Card>
                    </template>
                    <template v-else-if="item">
                        <h4 class="font-semibold text-gray-800 text-sm mb-1">Choisissez votre forfait</h4>
                        <EmptyState
                            v-if="!offresQuery?.meta?.total && !offresQuery.fetching"
                            title="Vous devez d'abord acheter le balance"
                            class="mt-5"
                            block
                            :actions="[
                                {
                                    label: 'Commander un forfait',
                                    variant: 'warning',
                                    href: route(routes.shop.index),
                                },
                            ]"
                        >
                            <p>Pour continuer la réservation des séance vous veulliez commendez un forfait</p>
                        </EmptyState>
                        <div v-else>
                            <div v-if="offresQuery.fetching" class="flex-center py-20">
                                <Spinner class="w-8 h-8" />
                            </div>
                            <RadioField
                                v-else
                                v-model="formReserve.offer_id"
                                :items="offresQuery.data"
                                class="shadow-box border-t border-l rounded-xl"
                            >
                                <template #content="{ item }">
                                    <div class="text-sm block">

                                        <div class="text-gray-600 mt-1">
                                            (Balance) :<b>{{ item.total_balance }}</b>h total
                                        </div>
                                        <span v-if="item.one_time_balance > 0 && item.installment_total_balance > 0" class="text-xs text-gray-500 block mt-1">
                                            ({{ item.one_time_balance }}h one-time + {{ item.installment_total_balance }}h in {{ item.installments }} installments = {{ item.per_installment_balance }}h per installment)
                                        </span>

                                    </div>

                                </template>
                            </RadioField>
                        </div>
                    </template>


                    <!-- Cancel 1 hour button -->
                    <div class="mt-4">
                        <Button
                            @click="onHourButtonClick"
                            :disabled="!canRequestHour"
                            class="bg-red-500 text-white px-20 py-1 rounded ml-20"
                        >
                            Annuler la séance {{ reservationHours }}h
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Reservation submit footer -->
            <div v-if="item && !item.training" class="sticky bottom-0 px-3 py-2 backdrop-blur-md">
                <Button
                    v-if="user.student?.balance"
                    variant="primary"
                    full
                    class="h-10"
                    :loading="formReserve.processing"
                    :disabled="!formReserve.offer_id"
                    @click="onSubmitReserve"
                >
                    Réserver le {{ dateFormat(item.date, 'letter') }} de {{ item.start_at }} à {{ item.end_at }}
                </Button>
                <Button v-else variant="warning" full class="h-10" :href="route(routes.shop.index)">
                    votre balance est vide, commander un offre
                </Button>
            </div>
        </Drawer>

        <!-- Full cancel dialog -->
        <DialogConfirm
            :show="!!formCancel.training_id"
            :loading="formCancel.processing"
            title="Confirmer l'annulation de séance"
            @close="formCancel.reset()"
            @confirm="onCancelConfirm"
        >
            Il vous reste <b>{{ timeLeftMessage.hours }}</b> heures avant la séance.
            <div :class="['mx-3 box p-1 mt-2 text-base/6', timeLeftMessage.className]">
                {{ timeLeftMessage.message }}
            </div>
            <div class="px-3 mt-5 -mb-10 py-2 text-left text-gray-950 bg-gray-100 border-b border-slate-300">
                <InputField v-model="formCancel.comment" :error="formCancel.errors.comment" :multiline="3" label="Justification" />
                <Errors :errors="formCancel.errors.training_id" />
            </div>
        </DialogConfirm>

        <!-- Hour adjustment request dialog -->
        <DialogConfirm
            :show="showCancelDialog"
            title="Demande de modification d'heures"
            :message="`Cette demande annulera toute la séance (${reservationHours}h) et restituera ${reservationHours}h au wallet après validation admin.`"
            confirmText="Envoyer"
            cancelText="Annuler"
            :loading="formHourRequest.processing"
            @confirm="onHourRequestConfirm"
            @close="showCancelDialog = false"
            @cancel="showCancelDialog = false"
        >
            <div class="px-3 mt-5 mb-2 py-2 text-left text-gray-950 bg-gray-100 border-b border-slate-300 space-y-4">


                <InputField
                    v-model="formHourRequest.comment"
                    :error="formHourRequest.errors.comment"
                    :multiline="3"
                    label="Commentaire"
                />
            </div>
        </DialogConfirm>

        <!-- All Comments Drawer -->
        <Drawer
            :show="stateLocal.showCommentDrawer"
            title="Tous les commentaires"
            max-width="md"
            @close="closeCommentDrawer"
        >
            <div class="p-4">
                <div v-if="state.loadingProposals || stateLocal.loadingComments" class="text-center py-8">
                    <Spinner class="w-8 h-8 mx-auto" />
                    <p class="text-gray-500 mt-2">Chargement des commentaires...</p>
                </div>

                <div v-else-if="allComments.length === 0" class="text-center py-8">
                    <EmptyState :image="AlertDiamondIcon" class="text-gray-400" heading="Aucun commentaire" />
                </div>

                <div v-else class="space-y-4 max-h-96 overflow-y-auto">
                    <div
                        v-for="(comment, index) in allComments"
                        :key="comment.id"
                        class="border-b pb-4 last:border-b-0"
                    >
                        <div class="flex justify-between items-start mb-2">
                            <span class="text-sm font-medium text-gray-700">
                                Commentaire #{{ index + 1 }}
                            </span>
                            <span class="text-xs text-gray-400">
                                {{ dateFormat(comment.created_at, 'short') }}
                            </span>
                        </div>
                        <p class="text-sm text-gray-600 whitespace-pre-wrap bg-gray-50 p-3 rounded">
                            {{ comment.comment }}
                        </p>
                    </div>
                </div>
            </div>
        </Drawer>
    </section>
</template>
