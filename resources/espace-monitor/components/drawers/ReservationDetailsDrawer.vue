<script setup lang="ts">
import { computed, reactive, watch } from 'vue';
import { Card, Button, DialogConfirm, Errors, InputField, Drawer, Thumb, EmptyState, Spinner } from '@shared/components';
import {
    NotificationIcon,
    CalendarIcon,
    ClockIcon,
    PhoneIcon,
    MenuHorizontalIcon,
    AlertDiamondIcon,
    EnvelopeSoftPackIcon,
} from '@adersolutions/icons';
import { routes } from '@espace-monitor/routes';
import { router, useForm } from '@inertiajs/vue3';
import { ActionSheet, ReservationLocateDetail, ReservationMessagesInfo, StudentStatsCard } from '@common/components';
import { dateFormat, getFilePath, isOutdated } from '@shared/utils';
import { useMonitorSpace, useReservationDetails } from '@espace-monitor/stores';
import { ReservationType } from '@common/types';
import { CancelStatusEnum } from '@common/enums';
import StudentMenuDrawer from './StudentMenuDrawer.vue';
import { SizeEnum } from '@shared/enums';
import { useMutation } from '@shared/hooks';
import { ButtonType } from '@shared/types';
import ReviewFormDrawer from './ReviewFormDrawer.vue';
import EvaluationDrawer from './EvaluationDrawer.vue';

const emit = defineEmits(['close', 'delete:availability', 'refresh']);

const details = useReservationDetails();
const space = useMonitorSpace();
const form = useForm({
    training_id: '',
    comment: '',
});
const formAval = useMutation({});
const item = computed(() => (details?.data || {}) as ReservationType);
const state = reactive({
    showEvaluation: false,
    showProposalDrawer: false,
    trainingProposals: [] as any[],
    loadingProposals: false,
});

// ---------------- TRAINING PROPOSALS ----------------
const fetchTrainingProposals = async () => {
    if (!item.value.id) return;

    state.loadingProposals = true;
    try {
        const response = await fetch(`/api/training-proposals?reservation_id=${item.value.id}`);
        const data = await response.json();
        state.trainingProposals = data.data || [];
    } catch (error) {
        console.error('Error fetching training proposals:', error);
        state.trainingProposals = [];
    } finally {
        state.loadingProposals = false;
    }
};

watch(() => item.value.id, (newId) => {
    if (newId) {
        fetchTrainingProposals();
        fetchReservationComments();
    }
}, { immediate: true });

type StatusType = 'pending' | 'reviewed' | 'unreviewed' | 'canceled' | 'estimation';
const status = computed<StatusType>(() => {
    const { training, review_monitor } = item.value || {};
    if (training?.cancellation?.status === CancelStatusEnum.SUCCESS_CANCELED) {
        return 'canceled';
    }
    if (!isOutdated(item.value)) {
        return 'pending';
    }
    const isReviewed = !!review_monitor?.comment || review_monitor?.is_absent ? 'reviewed' : 'unreviewed';
    if (review_monitor?.is_estimated && isReviewed === 'unreviewed') {
        return 'estimation';
    }
    return isReviewed;
});

const onCancel = () => {
    form.training_id = item.value.training?.id || '';
    form.comment = '';
};

const onClose = () => {
    details.close();
    emit('close');
};
const onConfirmCancellation = () => {
    form.post(route(routes.cancellations.store), {
        onSuccess: () => {
            form.reset();
            onClose();
            emit('refresh');
        },
    });
};
const onCancelAvailability = () => {
    formAval.delete(route(routes.api.reservations.destroy, item.value.id || '?')).then(() => {
        emit('delete:availability', item.value);
        onClose();
    });
};

const actions = (item: ReservationType): ButtonType[] => {
    const list: ButtonType[] = [
        {
            label: 'Proposer nouveau séance',
            variant: 'primary',
            onAction: () => {
                space.state.student = details.student || {};
                if (space.isReservationPage) {
                    details.show = false;
                    return;
                }
                router.visit(route(routes.reservations.index, { student_id: space.state.student.id }), {
                    onSuccess: () => {
                        details.show = false;
                    },
                });
            },
        },
    ];
    if (!isOutdated(item) && !details.isAvailability && !item.training?.cancellation) {
        list.push({
            label: 'Annuler la séance',
            variant: 'danger',
            onAction: onCancel,
        });
    }
    return list;
};
const onAddReview = () => {
    space.review.item = {
        ...(item.value?.review_monitor || {}),
        reservation: item.value,
    };
};
const onReviewSubmited = () => {
    emit('refresh');
    onClose();
};

// ---------------- RESERVATION COMMENTS ----------------
const stateLocal = reactive({
    showCommentDrawer: false,
    reservationComments: [] as any[],
    loadingComments: false,
});

const fetchReservationComments = async () => {
    if (!item.value.id) return;

    stateLocal.loadingComments = true;
    try {
        const response = await fetch(`/reservations/${item.value.id}/comments`);
        const data = await response.json();
        stateLocal.reservationComments = data.data || [];
    } catch (error) {
        console.error('Error fetching comments:', error);
        stateLocal.reservationComments = [];
    } finally {
        stateLocal.loadingComments = false;
    }
};

const showCommentDrawer = () => (stateLocal.showCommentDrawer = true);
const closeCommentDrawer = () => (stateLocal.showCommentDrawer = false);

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
</script>

<template>
    <div class="contents">
        <Drawer
            :show="details.show"
            :title="details.isAvailability ? 'Disponibilité' : 'Détails de reservation'"
            @close="onClose"
        >
            <template #actions>
                <ActionSheet :actions="actions(item)">
                    <MenuHorizontalIcon class="w-6 h-6" />
                </ActionSheet>
            </template>

            <div class="flex flex-col h-full">
                <div class="px-3 flex-1">
                    <ReservationMessagesInfo :is-availability="details.isAvailability" :item="item" />

                    <div class="text-dark py-4 text-md overflow-clip relative">
                        <div class="rainbow absolute -top-0.5 -left-20"></div>

                        <dl class="grid grid-cols-3 py-1">
                            <dd class="opacity-70 flex gap-2 items-center">
                                <CalendarIcon class="w-4" />
                                Date
                            </dd>
                            <dd class="col-span-2 font-semibold">
                                <span class="mr-2">:</span>
                                {{ dateFormat(item.date, 'full') }}
                            </dd>
                        </dl>

                        <dl class="grid grid-cols-3 py-1">
                            <dd class="opacity-70 flex gap-2 items-center">
                                <ClockIcon class="w-4" />
                                Heure
                            </dd>
                            <dd class="col-span-2 font-semibold">
                                <span class="mr-2">:</span>
                                {{ item.start_at }} à {{ item.end_at }}
                            </dd>
                        </dl>

                        <dl v-if="!details.isAvailability" class="grid grid-cols-3 py-1">
                            <dd class="opacity-70 flex gap-2 items-center">
                                <NotificationIcon class="w-4" />
                                Rappel
                            </dd>
                            <dd class="col-span-2 font-semibold">
                                <span class="mr-2">:</span>
                                {{ dateFormat(`${item.date} ${item.start_at}`, 'fromNow') }}
                            </dd>
                        </dl>
                    </div>

                    <ReservationLocateDetail :lieu="item.lieu" />

                    <!-- Training Section -->
                    <template v-if="item.training">
                        <Card title="Condidat" class="mt-4" v-if="details.student?.id">
                            <StudentStatsCard
                                class="mt-1"
                                :student="details.student"
                                :action="{
                                    label: 'Voir détail de Candidat',
                                    onAction: () => {
                                        details.user = details.student?.user || null;
                                    },
                                }"
                            />
                        </Card>

                        <Card v-if="item.training?.offer" title="Offre" class="mt-4">
                            <div
                                class="box w-full flex items-center gap-3 p-1 mt-1 bg-dark text-white relative overflow-clip bg-rainbow"
                                :style="{ backgroundColor: item.training?.offer?.color }"
                            >
                                <div class="bg-rainbow absolute -inset-[40%] opacity-50 z-0"></div>
                                <Thumb :src="getFilePath(item.training?.offer, true)" :size="SizeEnum.XS" />
                                <p class="font-semibold relative z-1 text-sm">
                                    {{ item.training?.offer?.name }}
                                </p>
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

                    <!-- Avis Section -->
                    <Card
                        title="Avis"
                        :class="[
                            'my-4 px-2 pt-2 box',
                            status === 'reviewed'
                                ? 'bg-white'
                                : status === 'unreviewed'
                                ? 'bg-yellow-50'
                                : 'bg-indigo-50',
                        ]"
                        :action="{
                            label: 'Voir tous les avis &rarr;',
                            link: true,
                            disabled: !details.student?.id,
                            href: route(routes.reviews.index, details.student?.id || '-'),
                        }"
                    >
                        <div v-if="status === 'reviewed'" class="text-sm divide-y border-t mt-4">
                            <div class="grid grid-cols-4 py-2">
                                <b>Statut</b>
                                <div class="col-span-3">
                                    <span
                                        :class="[
                                            item.review_monitor?.is_absent
                                                ? 'text-red-700 bg-red-200'
                                                : 'text-green-700 bg-green-200',
                                            'rounded px-3 ring-[0.5px] ring-current',
                                        ]"
                                    >
                                        {{ item.review_monitor?.is_absent ? 'Absent' : 'Présent' }}
                                    </span>
                                </div>
                            </div>
                            <div class="grid grid-cols-4 py-2">
                                <b>Note</b>
                                <div class="col-span-3">{{ item.review_monitor?.comment }}</div>
                            </div>
                        </div>

                        <EmptyState
                            v-else-if="status === 'estimation'"
                            :image="EnvelopeSoftPackIcon"
                            class="text-indigo-500 py-5"
                            heading="Merci d'avoir évalué de la première séance"
                        >
                            <p class="text-sm text-gray-500">
                                N'oubliez pas d'évaluer cette première séance et de confirmer votre réservation avec un commentaire
                                <br />
                                <span class="text-indigo-500 font-semibold">Merci de votre compréhension.</span>
                            </p>
                        </EmptyState>

                        <EmptyState
                            :image="status === 'unreviewed' ? AlertDiamondIcon : ClockIcon"
                            heading="Aucun avis n'est disponible pour le moment."
                            :class="['text-sm gap-1 pb-10', status === 'unreviewed' ? 'text-orange-600' : 'text-gray-500']"
                            v-else
                        >
                            <p :class="['-mt-0.5 flex-1', status === 'unreviewed' ? 'text-orange-600' : 'text-gray-500']">
                                {{
                                    status === 'pending'
                                        ? 'Ajoutez un avis après la séance'
                                        : 'Veuillez ajouter un avis pour confirmer la séance'
                                }}.
                            </p>
                        </EmptyState>
                    </Card>
                </div>

                <div class="page-actions bg-white">
                    <Button
                        v-if="details.isAvailability"
                        variant="danger"
                        full
                        :loading="formAval.processing"
                        @click="onCancelAvailability"
                    >
                        Annuler la disponibilité
                    </Button>

                    <Button
                        v-else-if="status === 'unreviewed'"
                        :icon="ClockIcon"
                        variant="orange"
                        full
                        @click="onAddReview"
                    >
                        Ajouter un avis et confirmez la séance
                    </Button>

                    <Button
                        v-else-if="status === 'estimation'"
                        :icon="EnvelopeSoftPackIcon"
                        variant="indigo"
                        full
                        @click="state.showEvaluation = true"
                    >
                        Évaluer et confirmer la séance
                    </Button>

                    <Button
                        v-else-if="details.student.user?.phone"
                        :icon="PhoneIcon"
                        variant="warning"
                        full
                        :href="`Tel:${details.student.user.phone}`"
                        self
                    >
                        Appel immédiat
                    </Button>
                </div>
            </div>
        </Drawer>

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

        <!-- Proposal Comments Drawer -->
        <Drawer
            :show="state.showProposalDrawer"
            title="Tous les commentaires de proposition"
            max-width="md"
            @close="state.showProposalDrawer = false"
        >
            <div class="p-4">
                <div v-if="state.loadingProposals" class="text-center py-8">
                    <Spinner class="w-8 h-8 mx-auto" />
                    <p class="text-gray-500 mt-2">Chargement des commentaires...</p>
                </div>

                <div v-else-if="state.trainingProposals.length === 0" class="text-center py-8">
                    <EmptyState :image="AlertDiamondIcon" class="text-gray-400" heading="Aucun commentaire">
                        <p class="text-sm text-gray-500">Aucun commentaire de proposition trouvé</p>
                    </EmptyState>
                </div>

                <div v-else class="space-y-4 max-h-96 overflow-y-auto">
                    <div
                        v-for="(comment, index) in state.trainingProposals"
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
                        <div class="flex items-center mt-2 space-x-2">
                            <span class="text-xs px-2 py-1 bg-blue-100 text-blue-800 rounded-full">
                                Statut: {{ comment.status }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </Drawer>

        <!-- Evaluation & Other Drawers -->
        <template v-if="!details.isAvailability">
            <EvaluationDrawer
                v-if="item.training?.student"
                :show="state.showEvaluation"
                :mid="item.monitor_id"
                :rid="item.id"
                :student="item.training.student"
                @close="state.showEvaluation = false"
                @review="onAddReview"
            />
            <StudentMenuDrawer />
            <ReviewFormDrawer :item="space.review.item" @close="space.review.item = null" @refresh="onReviewSubmited" />
            <DialogConfirm
                :show="!!form.training_id"
                :loading="form.processing"
                title="Confirmer l'annulation de seance"
                @close="form.reset()"
                @confirm="onConfirmCancellation"
            >
                Etes-vous sûr que vous pouvez annuler cette séance?
                <div class="px-3 mt-10 -mb-10 py-2 text-left text-gray-950 bg-gray-100 border-b border-slate-300">
                    <InputField
                        v-model="form.comment"
                        :error="form.errors.comment"
                        :multiline="3"
                        label="Justification"
                    />
                    <Errors :errors="form.errors.training_id" />
                </div>
            </DialogConfirm>
        </template>
    </div>
</template>
