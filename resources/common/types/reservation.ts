import type { PlaceType } from './area';
import type { CancellationsType } from './cancellation';
import type { OfferType } from './offer';
import type { MonitorType, StudentType } from './user';

export type ScheduleViewType = 'week' | 'month';

export type LessonType = {
    id: string;
    student_id: string;
    student?: StudentType;
    offer_id: string;
    offer?: OfferType;
    reservation?: ReservationType;
    cancellation?: CancellationsType;
};
export type ReviewType = {
    id: string;
    estimation: number;
    comment: string;
    is_absent: boolean;
    is_estimated: boolean;
    reservation?: ReservationType;
    monitor_id: string;
    student_id: string;
    created_at: string;
    updated_at: string;
    student?: StudentType;
    monitor?: MonitorType;
};
export type ReservationType = {
    id: string;
    monitor_id: string;
    date: string;
    start_at: string;
    end_at: string;
    hour: number;
    is_active: number | boolean;
    lieu_id: string;
    color: string;
    deleted_at: string | null;
    created_at: string;
    updated_at: string;
    day: number;
    datef: string;
    date_hour: number;
    training?: null | LessonType;
    monitor?: MonitorType;
    lieu?: PlaceType;
    lieux?: PlaceType[];
    review_monitor?: ReviewType;
};
