import { SizeEnum } from '@shared/enums';

export interface IMetaData {
    total: number;
    current_page: number;
    per_page: number;
    links: any[];
    path?: string;
    from?: number;
    to?: number;
}

export interface ILinks {
    first: string | null;
    prev: string | null;
    next: string | null;
    last: string | null;
}

export type DataListType<T> = {
    data: T;
    meta: IMetaData;
    links: ILinks;
    current_page?: number;
    next_page_url?: string;
    last_page?: string;
    total?: number;
    per_page?: number;
};

export type SizeListType = Partial<Record<SizeEnum, string>>;
