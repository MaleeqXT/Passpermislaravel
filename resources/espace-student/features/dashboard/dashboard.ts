import { ReservationType } from '@common/types';
import { routes } from '@espace-student/routes';
import { useQuery, useRoute } from '@shared/hooks';
import moment from 'moment-timezone';
import { computed, reactive } from 'vue';
export const tabs = [
    { name: 'Séance à venir', id: 'lecons-venir' },
    { name: 'Séance terminé', id: 'lecons-termine' },
    // { name: 'Mes Enseignants', id: 'enseignants-termine' },
];
export const useDashboard = () => {
    const params = useRoute<{ tab: string; page: string }>();
    const passed = useQuery<ReservationType[]>({
        url: route(routes.api.reservations.index, { is_passed: true }),
        transformable: true,
        mounted: true,
    });
    const next = useQuery<ReservationType[]>({
    url: route(routes.api.reservations.index),
    params: { is_unrestricted: false },
        transformable: true,
        mounted: true,
    });
    const refresh = () => {
        next.params.page = 1;
        passed.params.page = 1;

        next.fetch();
        passed.fetch();
    };
    const state = reactive<{ tab: string; selected: ReservationType | null }>({
        tab: params.tab || tabs[0].id,
        selected: null,
    });

    const todayRsv = computed(() => {
        return next.data.find((item) => {
            const isToday = item.datef === moment().format('yyyy-MM-DD');
            if (!isToday) {
                return undefined;
            }
            // return moment().isBetween(moment(item.start_at, 'HH:mm'), moment(item.end_at, 'HH:mm'), 'hours', '[)') &&
            return item;
        });
    });

    return {
        next,
        passed,
        refresh,
        todayRsv,
        state,
    };
};
