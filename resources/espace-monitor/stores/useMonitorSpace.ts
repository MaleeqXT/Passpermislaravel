import { computed, reactive, ref } from 'vue';
import { defineStore } from 'pinia';
import type { ProposalType, ReservationType, ReviewType, StudentType, UserType } from '@common/types';
import { usePage } from '@inertiajs/vue3';
import { routes } from '@espace-monitor/routes';
import { useInertia, useMutation, useRoute } from '@shared/hooks';
type StateType = {
    student: StudentType | null;
    selectedScheduleItem: ReservationType[];
};
type ProposalsStateType = {
    data: Partial<ProposalType>[];
    comment: string;
    selected: ProposalType | null;
    save: (callback?: () => void) => void;
    close: () => void;
    showConfirmation: boolean;
};
type ReviewStateType = {
    item: Partial<ReviewType> | null;
};
export const useMonitorSpace = defineStore('Monitor/Space', () => {
    const params = useRoute(false);
    const proposals = useMutation<ProposalsStateType>({
        data: [],
        comment: '',
        save: saveProposal,
        close: closeProposal,
        showConfirmation: false,
        selected: null,
    });
    const review = reactive<ReviewStateType>({
        item: null,
    });
    const page = usePage();
    const state = reactive<StateType>({
        student: (page.props.student as StudentType) || null,
        selectedScheduleItem: [],
    });

    const isReservationPage = computed(() => page.component === 'features/reservations/ReservationsPage');
    function closeProposal() {
        params.set({ student_id: null });
        state.student = null;
        proposals.data = [];
        proposals.comment = '';
        proposals.selected = null;
        proposals.showConfirmation = false;
    }
    function saveProposal(callback = () => {}) {
        proposals.transform((v) => ({
            data: v.data.map(({ reservation, ...args }) => ({ ...args, comment: v.comment })),
        }));
        proposals.post(route(routes.api.proposals.storeMany)).then(() => {
            callback?.();
        });
    }

    return {
        state,
        params,
        page,
        review,
        isReservationPage,
        proposals,
    };
});
