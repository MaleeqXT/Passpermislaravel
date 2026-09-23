import { ExamenStatusEnum } from '@common/enums';
import { EvaluationTypeEnum } from '@espace-admin/enums';
import type { PlaceType } from './area';
import type { MonitorType, StudentType, UserType } from './user';

export interface ExamType {
    id: string;
    student_id: string;
    user_id: number;
    monitor_id: string;
    lieu_id: string;
    paiement_forfait: boolean;
    paiement_ppe: boolean;
    is_auto: boolean | null;
    date_comment: string | null;
    type: EvaluationTypeEnum;
    date_pass_prevu: string;
    comment_account: string;
    comment: string;
    is_rdv_permis: boolean | null;
    date_examen: string | null;
    date_passage: string | null;
    heure_passage: string | null;
    result_permis: string | null;
    status: ExamenStatusEnum;
    created_at: string;
    updated_at: string;
    student: StudentType;
    user: UserType;
    monitor: MonitorType;
    lieu: PlaceType;
}
