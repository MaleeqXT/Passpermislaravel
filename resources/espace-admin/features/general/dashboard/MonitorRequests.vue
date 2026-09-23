<script setup lang="ts">
import { Page, Card, Button } from '@shared/components';
import { computed, ref, reactive } from 'vue';
import { usePage, useForm } from '@inertiajs/vue3';
import {
    CheckIcon,
    XIcon,
    ClockIcon,
    CalendarIcon,
    EditIcon,
    PlusIcon,
} from '@adersolutions/icons';

// Props from Inertia
const { props } = usePage();

interface ReservationRequest {
    id: string;
    monitor_id: string;
    hours_periods: Array<{ start: string; end: string }> | null;
    comment: string | null;
    status: string;
    created_at: string;
    updated_at: string;
    monitor: {
        id: string;
        user: {
            id: string;
            name: string;
            email: string;
        };
    };
}

// Requests list
const reservationRequests = computed(() => props.reservationRequests as ReservationRequest[]);

// State
const selectedRequest = ref<ReservationRequest | null>(null);
const editingHours = reactive<Array<{ start: string; end: string }>>([]);

// Form using Inertia
const updateForm = useForm({
    status: '',
    comment: '',
    hours_periods: [] as Array<{ start: string; end: string }>
});

const getStatusIcon = (status: string) => {
    switch (status) {
        case 'pending': return ClockIcon;
        case 'approved': return CheckIcon;
        case 'rejected': return XIcon;
        default: return ClockIcon;
    }
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

// Open Modal
const openEditModal = (request: ReservationRequest) => {
    selectedRequest.value = request;
    updateForm.status = request.status;
    updateForm.comment = request.comment || '';
    updateForm.hours_periods = request.hours_periods || [];

    editingHours.splice(0, editingHours.length, ...updateForm.hours_periods);

    if (editingHours.length === 0) {
        editingHours.push({ start: '09:00', end: '17:00' });
    }

    setTimeout(() => {
        const modalEl = document.getElementById('editModal');
        if (modalEl) {
            // @ts-ignore
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
        }
    }, 50);
};

// Close Modal
const closeEditModal = () => {
    const modalEl = document.getElementById('editModal');
    if (modalEl) {
        // @ts-ignore
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();
    }

    selectedRequest.value = null;
    editingHours.splice(0, editingHours.length);
    updateForm.reset();
};

// Add/remove slot
const addTimeSlot = () => {
    editingHours.push({ start: '09:00', end: '17:00' });
};
const removeTimeSlot = (index: number) => {
    editingHours.splice(index, 1);
};

// Approve/Reject status quickly
const updateStatus = (requestId: string, status: string) => {
    if (!confirm(`Are you sure you want to ${status} this request?`)) return;

    const form = useForm({ status });
    form.patch(route('reservation-requests.update', requestId), {
        preserveScroll: true
    });
};

// Submit Edit Form
const submitUpdate = () => {
    if (!selectedRequest.value) return;

    updateForm.hours_periods = editingHours;

    updateForm.patch(route('reservation-requests.update', selectedRequest.value.id), {
        preserveScroll: true,
        onSuccess: () => closeEditModal()
    });
};
</script>

<template>
    <Page padding="none" width="full" class="pb-20">
        <div class="container mx-auto px-4 py-6">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h2 text-dark mb-0">Monitor Reservation Requests</h1>
            </div>

            <!-- Table -->
            <Card class="border-0 shadow-sm">
                <div class="table-responsive">
                    <table class="table align-middle requests-table">
                        <thead>
                            <tr>
                                <th>Monitor</th>
                                <th>Comment</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="request in reservationRequests" :key="request.id">
                                <!-- Monitor -->
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="user-avatar me-3">
                                            {{ request.monitor.user.name.charAt(0).toUpperCase() }}
                                        </div>
                                        <div>
                                            <strong>{{ request.monitor.user.name }}</strong><br>
                                            <small class="text-muted">{{ request.monitor.user.email }}</small>
                                        </div>
                                    </div>
                                </td>

                                <!-- Comment -->
                                <td>
                                    <div class="comment-cell">
                                        <span v-if="request.comment">{{ request.comment }}</span>
                                        <span v-else class="text-muted fst-italic">No comment</span>
                                    </div>
                                </td>

                                <!-- Status -->
                                <td>
                                    <span class="status-badge"
                                          :class="{
                                              'status-pending': request.status === 'pending',
                                              'status-approved': request.status === 'approved',
                                              'status-rejected': request.status === 'rejected',
                                              'status-other': !['pending','approved','rejected'].includes(request.status)
                                          }">
                                        <component :is="getStatusIcon(request.status)" class="me-1 status-icon" />
                                        {{ request.status }}
                                    </span>
                                </td>

                                <!-- Date -->
                                <td>
                                    <div class="date-cell">
                                        <CalendarIcon class="me-1 date-icon" />
                                        {{ formatDate(request.created_at) }}
                                    </div>
                                </td>

                                <!-- Actions -->
                                <td class="text-end">
                                    <div class="btn-group actions-group">
                                        <Button v-if="request.status === 'pending'"
                                                variant="success" size="sm" class="action-btn"
                                                @click="updateStatus(request.id, 'approved')">
                                            <CheckIcon class="me-1 btn-icon" /> Approve
                                        </Button>
                                        <Button v-if="request.status === 'pending'"
                                                variant="danger" size="sm" class="action-btn"
                                                @click="updateStatus(request.id, 'rejected')">
                                            <XIcon class="me-1 btn-icon" /> Reject
                                        </Button>
                                        <Button variant="outline-primary" size="sm" class="action-btn" @click="openEditModal(request)">
                                            <EditIcon class="me-1 btn-icon" /> Edit
                                        </Button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty State -->
                            <tr v-if="reservationRequests.length === 0">
                                <td colspan="5" class="text-center py-5 empty-state">
                                    <ClockIcon class="mb-2 empty-icon" />
                                    <div class="fw-bold text-dark">No requests yet</div>
                                    <small class="text-muted">There are no reservation requests at the moment.</small>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </Card>

            <!-- Modal -->
            <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Edit Reservation Request</h5>
                            <button type="button" class="btn-close" @click="closeEditModal"></button>
                        </div>

                        <div class="modal-body" v-if="selectedRequest">
                            <!-- Monitor Info -->
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Monitor</label>
                                <div class="d-flex align-items-center">
                                    <div class="user-avatar me-3">
                                        {{ selectedRequest.monitor.user.name.charAt(0).toUpperCase() }}
                                    </div>
                                    <div>
                                        <div class="fw-semibold">{{ selectedRequest.monitor.user.name }}</div>
                                        <small class="text-muted">{{ selectedRequest.monitor.user.email }}</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Status -->
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Status</label>
                                <select v-model="updateForm.status" class="form-select">
                                    <option value="pending">Pending</option>
                                    <option value="approved">Approved</option>
                                    <option value="rejected">Rejected</option>
                                </select>
                            </div>

                            <!-- Hours -->
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Hours Periods</label>
                                <div v-for="(period, index) in editingHours" :key="index" class="d-flex gap-2 mb-2">
                                    <input type="time" v-model="period.start" class="form-control" />
                                    <input type="time" v-model="period.end" class="form-control" />
                                    <Button variant="danger" size="sm" @click="removeTimeSlot(index)" :disabled="editingHours.length <= 1">
                                        <XIcon class="btn-icon" />
                                    </Button>
                                </div>
                                <Button variant="outline-primary" size="sm" @click="addTimeSlot">
                                    <PlusIcon class="me-1 btn-icon" /> Add Slot
                                </Button>
                            </div>

                            <!-- Comment -->
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Comment</label>
                                <textarea v-model="updateForm.comment" class="form-control" rows="3" placeholder="Add a comment..."></textarea>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <Button variant="secondary" @click="closeEditModal">Cancel</Button>
                            <Button variant="primary" :loading="updateForm.processing" @click="submitUpdate">
                                <CheckIcon class="me-1 btn-icon" /> Update Request
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Page>
</template>

<style scoped>
/* Table styling */
.requests-table {
    border-collapse: separate;
    border-spacing: 0;
    width: 100%;
    font-size: 0.9rem;
}

.requests-table thead {
    background: linear-gradient(135deg, #4b6cb7 0%, #182848 100%);
}

.requests-table thead th {
    color: white;
    font-weight: 600;
    padding: 1rem 0.75rem;
    border: none;
    position: sticky;
    top: 0;
    z-index: 10;
}

.requests-table tbody tr {
    transition: all 0.2s ease;
    border-bottom: 1px solid #e9ecef;
}

.requests-table tbody tr:hover {
    background-color: #f8f9fa;
    transform: translateY(-1px);
    box-shadow: 0 4px 6px rgba(0,0,0,0.05);
}

.requests-table tbody td {
    padding: 1rem 0.75rem;
    vertical-align: middle;
}

/* User avatar */
.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 0.9rem;
}

/* Comment cell */
.comment-cell {
    max-width: 250px;
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}

/* Status badges */
.status-badge {
    padding: 0.35rem 0.75rem;
    border-radius: 50px;
    font-size: 0.8rem;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    text-transform: capitalize;
}

.status-pending {
    background-color: rgba(255, 193, 7, 0.15);
    color: #856404;
    border: 1px solid rgba(255, 193, 7, 0.3);
}

.status-approved {
    background-color: rgba(40, 167, 69, 0.15);
    color: #155724;
    border: 1px solid rgba(40, 167, 69, 0.3);
}

.status-rejected {
    background-color: rgba(220, 53, 69, 0.15);
    color: #721c24;
    border: 1px solid rgba(220, 53, 69, 0.3);
}

.status-other {
    background-color: rgba(108, 117, 125, 0.15);
    color: #383d41;
    border: 1px solid rgba(108, 117, 125, 0.3);
}

.status-icon {
    width: 14px;
    height: 14px;
}

/* Date cell */
.date-cell {
    display: flex;
    align-items: center;
    color: #6c757d;
    font-size: 0.85rem;
}

.date-icon {
    width: 14px;
    height: 14px;
}

/* Action buttons */
.actions-group {
    display: flex;
    gap: 0.35rem;
    flex-wrap: wrap;
    justify-content: flex-end;
}

.action-btn {
    border-radius: 6px;
    font-size: 0.8rem;
    padding: 0.25rem 0.5rem;
    white-space: nowrap;
}

.btn-icon {
    width: 12px;
    height: 12px;
}

/* Empty state */
.empty-state {
    padding: 3rem 1rem !important;
}

.empty-icon {
    width: 48px;
    height: 48px;
    color: #adb5bd;
}

/* Modal enhancements */
.modal-content {
    border: none;
    border-radius: 0.75rem;
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
}

.modal-header {
    border-bottom: 1px solid #e9ecef;
    padding: 1.25rem 1.5rem;
}

.modal-title {
    font-weight: 600;
    color: #2d3748;
}

.modal-body {
    padding: 1.5rem;
}

.modal-footer {
    border-top: 1px solid #e9ecef;
    padding: 1rem 1.5rem;
}

/* Responsive adjustments */
@media (max-width: 992px) {
    .table-responsive {
        border-radius: 0.5rem;
        overflow: hidden;
    }

    .requests-table thead {
        display: none;
    }

    .requests-table tbody tr {
        display: block;
        margin-bottom: 1rem;
        border: 1px solid #e9ecef;
        border-radius: 0.5rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }

    .requests-table tbody td {
        display: block;
        text-align: right;
        padding: 0.75rem;
        position: relative;
        padding-left: 50%;
    }

    .requests-table tbody td::before {
        content: attr(data-label);
        position: absolute;
        left: 0.75rem;
        width: calc(50% - 0.75rem);
        padding-right: 0.75rem;
        text-align: left;
        font-weight: 600;
        color: #495057;
    }

    /* Add data labels for mobile view */
    .requests-table tbody td:nth-child(1)::before { content: "Monitor"; }
    .requests-table tbody td:nth-child(2)::before { content: "Comment"; }
    .requests-table tbody td:nth-child(3)::before { content: "Status"; }
    .requests-table tbody td:nth-child(4)::before { content: "Date"; }
    .requests-table tbody td:nth-child(5)::before { content: "Actions"; }

    .actions-group {
        justify-content: flex-start;
    }

    .comment-cell {
        max-width: 100%;
        -webkit-line-clamp: 3;
    }
}
</style>
