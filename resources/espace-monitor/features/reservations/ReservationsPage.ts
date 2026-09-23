import { routes } from '@espace-monitor/routes';
import { useMutation, useQuery, type UseQueryType } from '@shared/hooks';
import { isTimeOverlapping } from '@shared/utils';
import { reactive, ref } from 'vue';
import type { PlaceType, ProposalType, ReservationType } from '@common/types';
import { ProposalStatusEnum } from '@common/enums';
import moment from 'moment-timezone';

export const useAvailability = (reservations: UseQueryType<Record<string, ReservationType[]>>) => {
    type FormReservationType = Partial<ReservationType & { disabled_for?: any; all?: boolean }>;
    type MutationType = {
        data: FormReservationType[];
        lieu_id: string | null;
    };
    const selected = ref<null | ReservationType>(null);
    const state = reactive<any>({
        showPlaces: false,
    });
    const form = useMutation<MutationType>({
        data: [],
        lieu_id: null,
    });

    const onDelete = () => {
        if (selected.value) {
            form.delete(route(routes.api.reservations.destroy, selected.value.id)).then(() => {
                reservations.fetch().finally(() => {
                    selected.value = null;
                });
            });
        }
    };

    const togglePlaces = () => {
        state.showPlaces = !state.showPlaces;
    };

    const onSave = ({ place }: { place: PlaceType }) => {
        form.transform((v) => ({
            lieu_id: place?.id || null,
            data: v.data.map(({ date_hour, disabled_for, all, ...els }) => els),
        }));

        form.post(route(routes.api.reservations.createMany)).then(() => {
            reservations.fetch().then(() => {
                form.data = [];
                form.lieu_id = null;
                togglePlaces();
            });
        });
    };

    const parsedData = (date: string) => {
        return form.data.filter((v) => v.date === date);
    };
    return {
        selected: selected.value,
        form,
        state,
        togglePlaces,
        delete: onDelete,
        save: onSave,
        data: parsedData,
    };
};
export const useProposals = (reservations: UseQueryType<Record<string, ReservationType[]>>) => {
    const query = useQuery<Record<string, ProposalType[]>>({
        url: route(routes.api.proposals.index),
        params: {
            upcomming: true,
            status: ProposalStatusEnum.PENDING,
        },
        mounted: true,
    });
    // Check if a reservation overlaps with any in the existing list
    const isExistIn = (reservation: ReservationType, existingReservations: ReservationType[]) => {
        if (!reservation.training) {
            return false;
        }
        return existingReservations.some((rev) => {
            return isTimeOverlapping(rev, reservation);
        });
    };

    // Filter proposals by date and ensure no overlapping reservations
    const parsedData = (date: string) => {
        const existingReservations = reservations.data[date] || [];
        return query.data[date]?.filter?.(({ reservation }) => !isExistIn(reservation, existingReservations)) || [];
    };

    return {
        data: parsedData,
        fetch: query.fetch,
        query,
    };
};

export type SelectedDaysType = Record<string, Record<number, string[]>>;

interface DateRangeForm {
    start_at: string;
    end_at: string;
    [key: string]: any;
}

export const getDaysOfWeekInMonth = (
    selectedDays: SelectedDaysType,
    currentDate: moment.Moment,
    weekDay: number,
    form: DateRangeForm
): number[] => {
    const activeDays: number[] = [];
    const currentMonth = currentDate.month() + 1;
    const date = moment(`${currentDate.year()}-${currentMonth}-01`, 'YYYY-MM-DD');
    const startDate = moment(form.start_at);
    const endDate = moment(form.end_at);

    // Adjust to the first occurrence of the given weekday in the month
    while (date.isoWeekday() !== weekDay) {
        date.add(1, 'day');
    }

    // Add days within the specified range that match the weekday
    while (date.month() + 1 === currentMonth) {
        const day = date.date();
        const formattedMonth = `${date.year()}-${(date.month() + 1).toString().padStart(2, '0')}`;

        // Check if day exists in selectedDays and falls within the specified date range
        if (selectedDays[formattedMonth] && selectedDays[formattedMonth][day] && date.isBetween(startDate, endDate, 'day', '[]')) {
            activeDays.push(day);
        }

        // Move to the next occurrence of the specified weekday
        date.add(7, 'days');
    }

    return activeDays;
};

export const getHoursByRange = (start = '07:00', end = '23:00') => {
    const hours = [];
    for (let i = parseInt(start); i < parseInt(end); i++) {
        hours.push({
            start_at: `${i.toString().padStart(2, '0')}:00`,
            end_at: `${(i + 1).toString().padStart(2, '0')}:00`,
        });
    }
    return hours;
};
