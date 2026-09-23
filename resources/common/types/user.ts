import { GeneralStatusEnum } from '@common/enums';
import type { AreaType, PlaceType } from './area';
import type { GlobalDataType } from './common';
import type { LessonType } from './reservation';
export type RoleType = Required<GlobalDataType> & {
    guard_name?: string;
    pivot?: {
        model_type: string;
        model_id: number;
        role_id: number;
    };
};
export interface MonitorDetails {
    id: string;
    monitor_id: string;
    experience: string;
    dernier_experience: string | null;
    details_experience: string | null;
    is_manual: string;
    is_auto: boolean;
    zone_souhaitee: string | null;
    departement: string | null;
    numero_autorisation: string | null;
    tarif_enseignement: string | null;
    tarif_car: string | null;
    deleted_at: string | null;
    created_at: string;
    updated_at: string;
}

export type MonitorType = Required<GlobalDataType> & {
    user_id: number;
    user?: UserType;
    details?: MonitorDetails;
    lieux?: PlaceType[];
    account?: {
        iban: string;
        bic: string;
    };
};

export type StudentType = Required<GlobalDataType> & {
    user_id: number;
    balance: number;
    neph: string | null;
    is_cpf: string;
    boite_type: string;
    date_code: string | null;
    how_know: string | null;
    frequence: string | null;
    contract_path: string | null;
    aval_monitor: boolean;
    user?: UserType;
    review_monitor: any | null;
    trainings: LessonType[];
    zones: AreaType[];
};


export interface ReservationRequestType {
    id: string;
    monitor_id: string;
    hours_periods: Array<{ start: string; end: string }>;
    comment: string | null;
    status: 'pending' | 'approved' | 'rejected';
    created_at: string;
    updated_at: string;
    monitor?: {
        user?: {
            name: string;
            email: string;
            phone: string;
            media?: string;
            profile_photo_url?: string;
        };
    };
}


export type UserType = {
    id: number;
    two_factor_confirmed_at: string | null;
    first_name: string;
    last_name: string;
    name: string;
    email: string;
    email_verified_at: string | null;
    media: string;
    google_id: string | null;
    status: GeneralStatusEnum;
    adresse: string | null;
    phone: string | null;
    sexe: string | null;
    date_naissance: string | null;
    postal: string | null;
    ville: string | null;
    created_at?: string;
    updated_at?: string;
    deleted_at?: string | null;
    profile_photo_url?: string;
    monitor?: MonitorType;
    student?: StudentType;
    roles: RoleType[];
    two_factor_enabled: boolean;
};
