<script setup lang="ts">
import { computed, reactive, watch } from 'vue';
import { Dialog, Button, Badge, Card, Drawer, Spinner, DialogConfirm } from '@shared/components';
import { dateFormat } from '@shared/utils';
import { useReservations } from '../../ReservationsPage';
import { StudentStatsCard } from '@common/components';
import { routes } from '@espace-admin/routes';
import { Link, useForm } from '@inertiajs/vue3';

const { state } = useReservations();

const event = computed(() => state.selected || {});

const stateLocal = reactive({
    showCommentDrawer: false,
    reservationComments: [] as any[],
    loadingComments: false,
    editingCommentId: null as string | null,
    editedComment: '',
    showDeleteConfirm: false,
    deletingCommentId: null as string | null,
});

// Fetch comments from reservation_comments API
const fetchReservationComments = async () => {
    if (!event.value.id) return;

    stateLocal.loadingComments = true;
    try {
        const response = await fetch(`/reservations/${event.value.id}/comments`);
        const data = await response.json();
        stateLocal.reservationComments = data.data || [];
    } catch (error) {
        console.error('Error fetching comments:', error);
        stateLocal.reservationComments = [];
    } finally {
        stateLocal.loadingComments = false;
    }
};

// Watch for reservation change
watch(
    () => event.value.id,
    (newId) => {
        if (newId) fetchReservationComments();
    },
    { immediate: true }
);

const showCommentDrawer = () => (stateLocal.showCommentDrawer = true);
const closeCommentDrawer = () => (stateLocal.showCommentDrawer = false);
const close = () => {
    state.selected = null;
    form.reset('comment');
};

// Computed: all comments
const allComments = computed(() => stateLocal.reservationComments || []);

// Computed: latest comment
const latestComment = computed(() => {
    if (allComments.value.length === 0) return null;
    return [...allComments.value].sort(
        (a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime()
    )[0];
});

const form = useForm<{
    student_id: string | null;
    comment: string;
}>({
    student_id: null,
    comment: '',
});

const editForm = useForm<{
    comment: string;
}>({
    comment: '',
});

const deleteForm = useForm<{}>({});

// Submit new comment
const onSubmitComment = async () => {
    if (!event.value.id) return;

    try {
        const res = await axios.post(route('reservations.comments.store', event.value.id), {
            student_id: event.value.training?.student?.id || null,
            comment: form.comment,
        });

        form.reset('comment');
        fetchReservationComments();
    } catch (error) {
        console.error(error.response?.data || error);
    }
};
// Edit functions
const startEdit = (comment) => {
    console.log('Starting edit for comment ID:', comment.id);
    stateLocal.editingCommentId = comment.id;
    stateLocal.editedComment = comment.comment;
    editForm.comment = comment.comment;
};

const cancelEdit = () => {
    console.log('Canceling edit');
    stateLocal.editingCommentId = null;
    stateLocal.editedComment = '';
    editForm.reset('comment');
};

const saveEdit = () => {
    if (!stateLocal.editingCommentId || !event.value.id) {
        console.error('Missing comment ID or reservation ID for editing');
        return;
    }

    console.log('Saving edit for comment ID:', stateLocal.editingCommentId, 'Reservation ID:', event.value.id);
    editForm.patch(route('reservations.comments.update', { reservation: event.value.id, comment: stateLocal.editingCommentId }), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            console.log('Comment updated successfully');
            fetchReservationComments();
            cancelEdit();
        },
        onError: (errors) => {
            console.error('Error updating comment:', errors);
        },
    });
};

// Delete functions
const confirmDelete = (id) => {
    console.log('Confirming delete for comment ID:', id);
    stateLocal.deletingCommentId = id;
    stateLocal.showDeleteConfirm = true;
};

const onDeleteComment = () => {
    if (!stateLocal.deletingCommentId || !event.value.id) {
        console.error('Missing comment ID or reservation ID for deletion');
        return;
    }

    console.log('Deleting comment ID:', stateLocal.deletingCommentId, 'Reservation ID:', event.value.id);
    deleteForm.delete(route('reservations.comments.destroy', { reservation: event.value.id, comment: stateLocal.deletingCommentId }), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            console.log('Comment deleted successfully');
            fetchReservationComments();
            stateLocal.showDeleteConfirm = false;
            stateLocal.deletingCommentId = null;
        },
        onError: (errors) => {
            console.error('Error deleting comment:', errors);
        },
    });
};
</script>

<template>
    <Dialog
        :show="!!state.selected?.id && !state.show && !state.delete && !state.updateToDispo"
        max-width="xs"
        title="Réservation Details"
        @close="close"
    >
        <div class="p-3 flex gap-5 border-y border-slate-300 bg-white">
            <div class="flex-1">
                <!-- Student Info -->
                <div class="pb-3" v-if="event?.training?.student">
                    <StudentStatsCard
                        :student="event.training.student"
                        :action="{ label: 'Voir le candidat', href: route(routes.users.students.general, event.training.student?.id) }"
                        mounted
                    />
                </div>

                <!-- Reservation Info -->
                <ul class="divide-y text-sm bg-gray-50 box">
                    <li class="grid grid-cols-3 py-1.5 px-3">
                        <span>Status:</span>
                        <div class="col-span-2">
                            <Badge
                                :value="{
                                    name: event?.is_active == 1 ? 'Actif' : 'Inactif',
                                    class: event?.is_active == 1 ? 'success' : 'danger',
                                }"
                            >
                                {{ event?.is_active == 1 ? 'Actif' : 'Inactif' }}
                            </Badge>
                        </div>
                    </li>
                    <li class="grid grid-cols-3 py-1.5 px-3">
                        <span>Date:</span>
                        <b class="col-span-2">{{ dateFormat(event?.date, 'fr') }}</b>
                    </li>
                    <li class="grid grid-cols-3 py-1.5 px-3">
                        <span>Heure:</span>
                        <b class="col-span-2">{{ event?.start_at?.slice(0, 5) }} - {{ event?.end_at?.slice(0, 5) }}</b>
                    </li>
                    <li class="grid grid-cols-3 py-1.5 px-3">
                        <span>Lieu:</span>
                        <b class="col-span-2">{{ event.lieu?.name }}</b>
                    </li>
                    <li v-if="event?.monitor" class="grid grid-cols-3 py-1.5 px-3">
                        <span>Moniteur:</span>
                        <Link
                            class="col-span-2 font-bold text-blue-500 underline"
                            :href="route(routes.users.monitors.edit, event.monitor?.id)"
                        >
                            {{ event.monitor?.user?.name }}
                        </Link>
                    </li>
                </ul>

                <!-- ================= COMMENTS ================= -->
                <Card title="Commentaires" class="mt-4">
                    <div class="p-3 bg-gray-50 rounded-lg">
                        <div v-if="stateLocal.loadingComments" class="text-center py-4">
                            <Spinner class="w-6 h-6 mx-auto" />
                            <p class="text-sm text-gray-500 mt-2">Chargement des commentaires...</p>
                        </div>
                        <template v-else>
                            <!-- Latest Comment -->
                            <div v-if="latestComment" class="mb-4">
                                <p class="text-sm font-medium text-gray-700">Dernier commentaire:</p>
                                <p class="text-sm text-gray-600 whitespace-pre-wrap mt-1 p-2 bg-white rounded border">
                                    {{ latestComment.comment }}
                                </p>
                                <p class="text-xs text-gray-400 mt-1">
                                    Ajouté le {{ dateFormat(latestComment.created_at, 'short') }}
                                </p>
                            </div>

                            <div v-if="allComments.length > 0" class="mb-3">
                                <p class="text-xs text-gray-500">{{ allComments.length }} commentaire(s)</p>
                            </div>

                            <div v-if="allComments.length === 0" class="mb-4">
                                <span class="text-gray-400 font-light text-sm">Aucun commentaire</span>
                            </div>

                            <Button
                                v-if="allComments.length > 0"
                                variant="secondary"
                                size="sm"
                                @click="showCommentDrawer"
                                class="mt-2"
                            >
                                Voir tous les commentaires ({{ allComments.length }})
                            </Button>
                        </template>
                    </div>
                </Card>

                <!-- ================= ADD COMMENT FORM ================= -->
                <Card title="Ajouter un commentaire" class="mt-4">
                    <form @submit.prevent="onSubmitComment">
                        <textarea
                            v-model="form.comment"
                            rows="3"
                            class="w-full border rounded p-2 text-sm"
                            placeholder="Écrire un commentaire..."
                        ></textarea>
                        <Button
                            type="submit"
                            variant="primary"
                            size="sm"
                            class="mt-2"
                            :disabled="form.processing"
                        >
                            Enregistrer le commentaire
                        </Button>
                    </form>
                </Card>

                <Button
                    v-if="event?.training"
                    variant="secondary"
                    full
                    class="mt-1"
                    @click="state.updateToDispo = true"
                >
                    Mettre comme disponible
                </Button>
            </div>
        </div>

        <!-- Footer -->
        <div class="flex flex-row items-center justify-between p-4 bg-gray-100 text-right">
            <div class="flex gap-2 justify-end">
                <Button link variant="danger" @click="state.delete = true">Supprimer</Button>
            </div>
            <div class="flex gap-2">
                <Button variant="info" @click="state.show = true">Modifier la Séance</Button>
            </div>
        </div>
    </Dialog>

    <!-- ================= DRAWER ALL COMMENTS ================= -->
    <Drawer
        :show="stateLocal.showCommentDrawer"
        title="Tous les commentaires"
        max-width="md"
        @close="closeCommentDrawer"
    >
        <div class="p-4">
            <div v-if="stateLocal.loadingComments" class="text-center py-8">
                <Spinner class="w-8 h-8 mx-auto" />
                <p class="text-gray-500 mt-2">Chargement des commentaires...</p>
            </div>

            <div v-else-if="allComments.length === 0" class="text-center py-8">
                <p class="text-gray-400">Aucun commentaire trouvé</p>
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
                    <div v-if="stateLocal.editingCommentId === comment.id" class="flex flex-col gap-2">
                        <textarea v-model="editForm.comment" rows="3" class="w-full border rounded p-2 text-sm"></textarea>
                        <div class="flex gap-2">
                            <Button variant="primary" size="sm" @click="saveEdit">Enregistrer</Button>
                            <Button variant="secondary" size="sm" @click="cancelEdit">Annuler</Button>
                        </div>
                    </div>
                    <p v-else class="text-sm text-gray-600 whitespace-pre-wrap bg-gray-50 p-3 rounded">
                        {{ comment.comment }}
                    </p>
                    <div class="flex gap-2 mt-2">
                        <Button variant="link" size="sm" @click="startEdit(comment)">Modifier</Button>
                        <Button variant="link" size="sm" class="text-red-500" @click="confirmDelete(comment.id)">Supprimer</Button>
                    </div>
                </div>
            </div>
        </div>
    </Drawer>

    <!-- ================= DELETE CONFIRMATION ================= -->
    <DialogConfirm
        :show="stateLocal.showDeleteConfirm"
        title="Confirmer la suppression"
        message="Êtes-vous sûr de vouloir supprimer ce commentaire ?"
        @close="stateLocal.showDeleteConfirm = false"
        @confirm="onDeleteComment"
    />
</template>
