import { ProposalStatusEnum } from '@common/enums';
import type { StudentType } from './user';
import type { ReservationType } from './reservation';

export type ProposalType = {
    id: string;
    reservation_id: string;
    student_id?: string;
    reservation: ReservationType;
    student: StudentType;
    status: ProposalStatusEnum;
    comment?: string;
    created_at: string;
    updated_at: string;
};
