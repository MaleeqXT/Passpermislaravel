import { MediaType } from '@shared/types';
import { MonitorType } from './user';

// Top-level Car
export type CarType = {
    id: string;
    marque: string;
    modele: string;
    color: string;
    immatriculation: string;
    date_achat: string;
    date_control_tech: string;
    date_assurance: string;
    is_auto: boolean;
    monitor_id: string;
    created_at: string;
    updated_at: string;
    monitor: MonitorType;
    gray_car_cart: GrayCarCartType;
    assurance: AssuranceType;
};

// GrayCarCart object
export type GrayCarCartType = {
    id: string;
    car_id: string;
    created_at: string;
    updated_at: string;
    media: MediaType[];
};

// Assurance object attached to the Car
export type AssuranceType = {
    id: string;
    car_id: string;
    created_at: string;
    updated_at: string;
    media: MediaType[];
};
