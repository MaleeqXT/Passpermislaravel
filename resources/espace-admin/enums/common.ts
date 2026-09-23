// Enum to represent the IDs
export enum EvaluationTypeEnum {
    HEUR_EVAL = 1,
    ATTEND_NEPH = 2,
    HEUR_EVAL_FORAIS = 3,
    ACCES_CODE = 4,
    NEPH = 5,
}

export enum ExamStatusEnum {
    LIST_ATTENTE = 1,
    PRE_LIST = 2,
    PASSAGE = 3,
    ARCHIVE = 4,
}

// Map the `EvaluationTypeEnum` enum values to their respective properties
export const evaluationDetails = {
    [EvaluationTypeEnum.HEUR_EVAL]: {
        name: "Heure d'évaluation",
        sn: 'HE',
        class: 'warning',
    },
    [EvaluationTypeEnum.ATTEND_NEPH]: {
        name: 'Attente NEPH - Acces code - 1H bilan MAX',
        sn: 'A-NEPH',
        class: 'danger',
    },
    [EvaluationTypeEnum.HEUR_EVAL_FORAIS]: {
        name: "Heure d'éval Forfait",
        sn: 'HEF',
        class: 'success',
    },
    [EvaluationTypeEnum.ACCES_CODE]: {
        name: 'Accès code',
        sn: 'AC',
        class: 'dark',
    },
    [EvaluationTypeEnum.NEPH]: {
        name: 'NEPH €',
        sn: 'NEPH',
        class: 'info',
    },
};
