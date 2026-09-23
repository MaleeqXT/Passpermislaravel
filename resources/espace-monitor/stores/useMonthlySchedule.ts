import { ref } from 'vue';
import { defineStore } from 'pinia';
import { useDimensions, useQuery } from '@shared/hooks';
import { routes } from '@espace-monitor/routes';
export const useMonthlySchedule = defineStore('Monitor/MonthlySchedule', () => {
    const { md } = useDimensions();
    const show = ref(false);
    const filters = ref({});
    const eventsQuery = useQuery<Record<string, { reserved: number; dispo: number }>>({
        url: route(routes.api.reservations.events),
        params: filters.value,
        dataType: {},
    });

    const open = () => {
        show.value = true;
        if (!Object.keys(eventsQuery.data).length) {
            eventsQuery.fetch();
        }
    };

    const close = () => {
        show.value = false;
    };

    return {
        show,
        open,
        close,
        eventsQuery,
        md,
    };
});
