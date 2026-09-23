import moment from 'moment-timezone';
import { nextTick, onMounted, reactive, watch } from 'vue';
import { routes } from '@espace-secretary/routes';
import { useDebounce, useEvents, useQuery, useRoute, useStorage } from '@shared/hooks';
import { defineStore } from 'pinia';
import type { AreaType, MonitorType, PlaceType, ReservationType, UserType } from '@common/types';
import { isOutdated } from '@shared/utils';

type UseReservationStateType = {
    show: boolean;
    updateToDispo: boolean;
    selected: Partial<ReservationType> | null;
    delete: boolean;
    create: boolean;
};


type UseReservationFiltersType = {
    start: string;
    end: string;
    disp: boolean | undefined;
    lieu_id: PlaceType | null;
    zone_id: AreaType | null;
    view: 'week' | 'month';
    monitor_id: UserType[];
    student_id: UserType[];
};

// Check session date
export const getReservationDate = (count: number, format: string = 'YYYY-MM-DD'): string => {
    const { filters } = useReservations() || {};
    return moment(filters.start).add(count, 'd').format(format);
};

export const extractUniqueMonitors = (data: Record<string, ReservationType[]>): Record<string, { count: number; list: MonitorType[] }> => {
    const result: Record<string, { count: number; list: MonitorType[] }> = {};

    for (const date in data) {
        const reservations = data[date];
        const uniqueMonitors = new Map<string, MonitorType>(); // Store unique monitor objects
        reservations.forEach((entry: ReservationType) => {
            entry.monitor && uniqueMonitors.set(entry.monitor_id, entry.monitor);
        });
        // Prepare result with count and list of unique monitors
        result[date] = {
            count: uniqueMonitors.size,
            list: Array.from(uniqueMonitors.values()), // Convert the map values (monitor objects) to an array
        };
    }
    return result;
};

export const listDays: string[] = moment.weekdays(true);
export const STORAHE_KEY_RESERVATION = 'RESERVATION:TRAINING';
export const getColor = (item: ReservationType) => {
    const obj = {
        className:
            ' border border-dashed  ' +
            (isOutdated(item) ? '!bg-gray-50 border-gray-300 ' : '!bg-green-50 border-green-500 text-green-600'),
        style: {},
    };
    let color = '#bee9cd';
    if (item.training) {
        color = item?.color || item.training.offer?.color || '#02161D';
        obj.className = 'text-white bg-rainbow bg-custom';
    }
    obj.style = { '--customcolor': color };
    return obj;
};
export const useReservations = defineStore('Reservations/Training', () => {
    const params = useRoute<UseReservationFiltersType>();
    const [storage, setStorage] = useStorage<Partial<UseReservationFiltersType>>(STORAHE_KEY_RESERVATION, {});
    const debounce = useDebounce(50);
    const events = useEvents();
    const filters = reactive<UseReservationFiltersType>({
        start: storage.value?.view === 'month' ? monthChange(params.start, 0).start : weekChange(params.start, 0).start,
        end: storage.value?.view === 'month' ? monthChange(params.end, 0).end : weekChange(params.end, 0).end,
        zone_id: storage.value?.zone_id || null,
        lieu_id: storage.value?.lieu_id || null,
        view: storage.value?.view || 'week',
        disp: Boolean(storage.value?.disp),
        monitor_id: storage.value?.monitor_id || [],
        student_id: storage.value?.student_id || [],
    });

    const query = useQuery<Record<string, ReservationType[]>>({
        url: route(routes.api.reservations.index),
        params: { ...parsedFilters(filters), is_unrestricted: false },
        mounted: !!filters.monitor_id?.length,
    });
    const activeMonth = () => {
        if (filters.view === 'month') {
            return moment(filters.start).add(15, 'd');
        }
        return moment(filters.start).add(4, 'd');
    };
    const state = reactive<UseReservationStateType>({
        show: false,
        updateToDispo: false,
        create: false,
        delete: false,
        selected: null,
    });

    function monthChange(start: string | undefined, i: number) {
        const d = moment(start).add(15, 'd').add(i, 'M');
        return {
            start: d.clone().startOf('month').startOf('isoWeek').format('YYYY-MM-DD'),
            end: d.clone().endOf('month').endOf('isoWeek').format('YYYY-MM-DD'),
        };
    }

    function weekChange(start: string | undefined, i: number) {
        const d = moment(start).add(i, 'w');
        return {
            start: d.clone().startOf('isoWeek').format('YYYY-MM-DD'),
            end: d.clone().endOf('isoWeek').format('YYYY-MM-DD'),
        };
    }
    const onDateChange = (i = 0) => {
        const { start, end } = filters.view === 'month' ? monthChange(filters.start, i) : weekChange(filters.start, i);
        filters.start = start;
        filters.end = end;
        params.set({ start, end });
        events.emit(events.keys.schedule.date, start);
    };
    function parsedFilters(f: UseReservationFiltersType) {
        return {
            ...f,
            monitor_id: f.monitor_id?.map(({ id }: { id: number }) => id),
            student_id: f.student_id?.map(({ id }: { id: number }) => id),
            lieu_id: f.lieu_id?.id,
            zone_id: f.zone_id?.id,
        };
    }

    // Watch for changes and trigger fetch
    watch(
        filters,
        debounce(({ start, end, ...v }: UseReservationFiltersType) => {
            setStorage(v);
            console.log(v);
            params.set({ start, end });
            query.params = { ...parsedFilters({ ...v, start, end }), is_unrestricted: false };
            query.fetch();
        })
    );
    onMounted(() => {
        // nextTick(() => {
        //     setTimeout(() => {
        //         console.log('filters.start', filters.start);
        //         events.emit(events.keys.schedule.date, filters.start);
        //     }, 200);
        // });
    });
    return {
        filters,
        state,
        query,
        onDateChange,
        activeMonth,
        parsedFilters,
    };
});
