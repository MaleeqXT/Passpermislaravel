import { GlobalListType } from '@common/types';

export enum OffreCategoryEnum {
    CODE = 1,
    CONDUITE = 2,
}

// Enum for offer types
export enum OffreTypeEnum {
    FORFAIT = 1,
    EXAMEN,
    CODE_ONLINE,
}

// Enum for isAutoEnum
export enum LearningModeEnum {
    ACTIVE = 1,
    INACTIVE = 0,
}

// Define the offer state object with descriptive class names for states like "info", "danger", "dark"
export const OffreTypeList: GlobalListType<OffreTypeEnum> = {
    [OffreTypeEnum.FORFAIT]: {
        name: 'Forfait',
        id: OffreTypeEnum.FORFAIT,
        class: 'primary',
        sn: 'Forfait',
    },
    [OffreTypeEnum.EXAMEN]: {
        name: 'Examen pratique',
        id: OffreTypeEnum.EXAMEN,
        class: 'warning',
        sn: 'EX',
    },
    [OffreTypeEnum.CODE_ONLINE]: {
        name: 'Code en ligne',
        id: OffreTypeEnum.CODE_ONLINE,
        class: 'success',
        sn: 'CL',
    },
};

// Mapping for OfferType
export const OffreCategoryList: GlobalListType<OffreCategoryEnum> = {
    [OffreCategoryEnum.CODE]: {
        id: OffreCategoryEnum.CODE,
        name: 'Offre Formation Code de la Route',
        class: 'info',
        sn: 'OFC',
    },
    [OffreCategoryEnum.CONDUITE]: {
        id: OffreCategoryEnum.CONDUITE,
        name: 'Offre Cours de Conduite',
        class: 'success',
        sn: 'OCC',
    },
};

// Mapping for LearningModeEnum
export const LearningModeList: GlobalListType<LearningModeEnum> = {
    [LearningModeEnum.ACTIVE]: {
        id: LearningModeEnum.ACTIVE,
        name: 'Boite Automatique',
        sn: 'BA',
        class: 'success',
    },
    [LearningModeEnum.INACTIVE]: {
        id: LearningModeEnum.INACTIVE,
        name: 'Boite Manuel',
        sn: 'BM',
        class: 'warning',
    },
};
