import { computed, ref } from 'vue';
import { defineStore } from 'pinia';
import type { ReservationType, StudentType, UserType } from '@common/types';
export const useReservationDetails = defineStore('Monitor/ReservationDetails', () => {
    const show = ref(false);
    const data = ref<ReservationType | null>(null);
    const review = ref(null);
    const user = ref<UserType | null>(null);
    const student = computed((): StudentType => data.value?.training?.student || ({} as StudentType));
    const isAvailability = computed(() => !data.value?.training);

    const open = (value: ReservationType) => {
        show.value = true;
        data.value = value;
    };
    const close = () => {
        show.value = false;
    };

    const clear = () => {
        data.value = null;
    };
    return {
        student,
        review,
        user,
        isAvailability,
        show,
        data,
        open,
        clear,
        close,
    };
});
