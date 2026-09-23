import type { GlobalListType } from '@common/types';

export enum ExamenStatusEnum {
    SUCCESS = 1,
    FAILED = 2,
    PENDING = 3,
}

export enum ExamenResultEnum {
    ADMIS = 1,
    REFUS = 2,
}

export const ExamenStatusList: GlobalListType<ExamenStatusEnum> = {
    [ExamenStatusEnum.SUCCESS]: {
        id: ExamenStatusEnum.SUCCESS,
        name: 'Réussi',
        class: 'success',
    },
    [ExamenStatusEnum.FAILED]: {
        id: ExamenStatusEnum.FAILED,
        name: 'Echoué',
        class: 'danger',
    },
    [ExamenStatusEnum.PENDING]: {
        id: ExamenStatusEnum.PENDING,
        name: 'En attente',
        class: 'default',
    },
};

export const ExamenResultList: GlobalListType<ExamenResultEnum> = {
    [ExamenResultEnum.ADMIS]: { id: ExamenResultEnum.ADMIS, name: 'Refus', class: 'danger' },
    [ExamenResultEnum.REFUS]: { id: ExamenResultEnum.REFUS, name: 'Admis', class: 'success' },
};
