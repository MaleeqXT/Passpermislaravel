import { GlobalListType, GlobalObjType } from '@common/types';

export const TabAll = {
    name: 'Tous',
    id: '',
};
export enum GeneralStatusEnum {
    ACTIVE = 1,
    INACTIVE,
    INPROGRESS,
}

export const ActivationStatus: Partial<GlobalListType<GeneralStatusEnum>> = {
    [GeneralStatusEnum.ACTIVE]: {
        id: GeneralStatusEnum.ACTIVE,
        name: 'Actif',
        class: 'success',
    },
    [GeneralStatusEnum.INACTIVE]: {
        id: GeneralStatusEnum.INACTIVE,
        name: 'Inactif',
        class: 'danger',
    },
};
export const GeneralStatus: Partial<GlobalListType<GeneralStatusEnum>> = {
    ...ActivationStatus,
    [GeneralStatusEnum.INPROGRESS]: {
        id: GeneralStatusEnum.INPROGRESS,
        name: 'En attente',
        class: 'secondary',
    },
};

// Enum for Yes/No Status
export enum YesNoEnum {
    YES = 1,
    NO = 0,
}

// Interface for defining the Yes/No structure
// Map of Yes/No status
export const YesNoStatus: GlobalListType<YesNoEnum> = {
    [YesNoEnum.YES]: {
        id: YesNoEnum.YES,
        name: 'Oui',
        class: 'success',
    },
    [YesNoEnum.NO]: {
        id: YesNoEnum.NO,
        name: 'Non',
        class: 'danger',
    },
};

// Enum for Storage Types
export enum StorageTypeEnum {
    LOCAL = 'local',
    SESSION = 'session',
}

// Constants for Indefini
export const Indefini: GlobalObjType<string> = {
    class: 'default',
    name: 'Indéfini',
    id: '',
};
