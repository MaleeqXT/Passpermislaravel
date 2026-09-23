import type { StudentType, UserType } from './user';

export type StudentCommentType = {
    id: string;
    comment: string;
    created_at: string;
    student: StudentType;
    user: UserType;
};
