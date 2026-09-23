import { CancelStatusEnum } from '@common/enums';
import { LessonType } from './reservation';
import { StudentType } from './user';

export type CancellationsType = {
    id: string;
    training_id: string;
    is_justified: number;
    status: CancelStatusEnum;
    training?: LessonType;
    comment: string;
    created_at: string;
    updated_at: string;
    media: any;
};
