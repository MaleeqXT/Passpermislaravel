import { computed, reactive, ref, watch } from 'vue';
import { defineStore } from 'pinia';
import { useQuery, useStorage } from '@shared/hooks';
import { routes as adminRoute } from '@espace-admin/routes';
import type { AreaType, PlaceType, UserType } from '@common/types';
import { usePage } from '@inertiajs/vue3';

type LocationsStateType = {
    show: boolean;
    area: string;
    place: string;
    set: () => void;
    open: () => void;
};
type StatsType = {
    reservations: { passed: number; upcoming: number };
    balance: { rest: number; used: number };
    contract: { available: boolean };
    competences: { total: number; done: number };
};
export const useStudentSpace = defineStore('Student/StudentSpace', () => {
    const [storage, setValue] = useStorage<{ area: string; place: string }>('student/location', null);
    const user = (usePage().props.auth as { user: UserType })?.user;
    const areaQuery = useQuery<AreaType[]>({
        url: route(adminRoute.api.locations.area.index),
        mounted: true,
    });
    const placesQuery = useQuery<PlaceType[]>({
        url: route(adminRoute.api.locations.places.index, storage.value?.area || '1'),
        transformable: true,
        callback: (d = []) => d.map((v: PlaceType) => ({ ...v, ...v.data })),
    });
    const locations = reactive<LocationsStateType>({
        show: !storage.value?.place,
        area: storage.value?.area || '',
        place: storage.value?.place || '',
        set: () => {
            setValue({ area: locations.area, place: locations.place });
            locations.show = false;
        },
        open: () => {
            locations.show = true;
            !areaQuery?.data?.length && areaQuery.fetch();
            !placesQuery?.data?.length && locations.area && placesQuery.fetch();
        },
    });
    const stats = useQuery<StatsType>({
        url: route('api.stats.students.index', user.student?.id || '?'),
        mounted: !!user.student?.id,
        init: {
            data: {
                reservations: { passed: 0, upcoming: 0 },
                balance: { rest: 0, used: 0 },
                contract: { available: false },
                competences: { total: 0, done: 0 },
            },
        },
    });

    return {
        locations,
        areaQuery,
        placesQuery,
        storage,
        stats,
        user,
    };
});
