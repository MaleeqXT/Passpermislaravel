import { PageTypeEnum } from '@common/enums';
import { UserType } from './user';

export type PageType<T> = {
    id: number;
    title: string;
    type: PageTypeEnum;
    is_active: boolean;
    extra: T;
    start_at: string;
    end_at: string;
    created_at: string;
    updated_at: string;
    deleted_at: string | null;
    user_id: string;
    user?: UserType;
    location?: string;
};
