import type { GlobalDataType } from './common';

export type AreaType = Required<GlobalDataType> & {
    lieux?: PlaceType[];
};

export type PlaceType = Required<GlobalDataType> & {
    zone_id?: string;
    url?: string | null;
    zone?: AreaType;
};
