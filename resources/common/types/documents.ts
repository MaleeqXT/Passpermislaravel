import type { MediaType } from '@shared/types';
import type { MonitorType } from './user';

export type IdentityDocumentType = {
    id: string;
    monitor_id: string;
    monitor?: MonitorType;
    created_at: string;
    updated_at: string;
    media: MediaType[];
};
export type PermitDocumentType = {
    id: string;
    monitor_id: string;
    monitor?: MonitorType;
    created_at: string;
    updated_at: string;
    media: MediaType[];
};
export type DiplomaDocumentType = {
    id: string;
    monitor_id: string;
    monitor?: MonitorType;
    created_at: string;
    updated_at: string;
    media: MediaType[];
};
export type InstructorPermissionType = {
    id: string;
    instructor_document_id: string;
    autorisation: string;
    visite: string;
    created_at: string;
    updated_at: string;
    media: MediaType[];
};
export type ProDocumentType = {
    id: string;
    monitor_id: string;
    monitor?: MonitorType;
    denomination_social: string;
    forme_juridique: string;
    date_creation: string;
    siret: string;
    num_autorisation: string;
    created_at: string;
    updated_at: string;
    instructor_permission: InstructorPermissionType;
};
