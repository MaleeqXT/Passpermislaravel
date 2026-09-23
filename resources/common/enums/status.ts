// Enum for Proposition Status

// Enum for Annulation Status
export enum CancelStatusEnum {
    PENDING = 1,
    REFUSED,
    SUCCESS_CANCELED,
}

// Enum for CPF Status
export enum CPFStatusEnum {
    ACCEPTED = 1,
    FORMATION,
    FORMATION_EXIT,
    SERVICE_DECLARED,
    SERVICE_VALID,
    FACTURE,
    KO,
}

// Enum for Facture Reservation Status
export enum FactureReservationStatusEnum {
    FACTURABLE = 1,
    NON_FACTURABLE,
}

// Proposition Reservation Status

// Annulation Status
export const CancelStatus = {
    [CancelStatusEnum.PENDING]: {
        id: CancelStatusEnum.PENDING,
        name: 'En attente',
        class: 'warning',
        desc: "Demande d'annulation est en cours de traitement.",
    },
    [CancelStatusEnum.REFUSED]: {
        id: CancelStatusEnum.REFUSED,
        name: 'Refusée',
        class: 'alert',
        desc: 'L\'annulation est refusée, veuillez contacter le support.',
    },
    [CancelStatusEnum.SUCCESS_CANCELED]: {
        id: CancelStatusEnum.SUCCESS_CANCELED,
        name: 'Annulée',
        class: 'success',
        desc: 'Réservation est annulée.',
    },
};

// CPF Status
export const CPFStatus = {
    [CPFStatusEnum.ACCEPTED]: { id: CPFStatusEnum.ACCEPTED, name: 'Accepté', class: 'warning' },
    [CPFStatusEnum.FORMATION]: { id: CPFStatusEnum.FORMATION, name: 'En Formation', class: 'success' },
    [CPFStatusEnum.FORMATION_EXIT]: { id: CPFStatusEnum.FORMATION_EXIT, name: 'Sortie de Formation', class: 'dark' },
    [CPFStatusEnum.SERVICE_DECLARED]: {
        id: CPFStatusEnum.SERVICE_DECLARED,
        name: 'Service Fait Déclaré',
        class: 'info',
    },
    [CPFStatusEnum.SERVICE_VALID]: { id: CPFStatusEnum.SERVICE_VALID, name: 'Service Fait Validé', class: 'info' },
    [CPFStatusEnum.FACTURE]: { id: CPFStatusEnum.FACTURE, name: 'Facturé', class: 'warning' },
    [CPFStatusEnum.KO]: { id: CPFStatusEnum.KO, name: 'KO', class: 'danger' },
};

// Facture Reservation Status
export const FactureReservationStatus = {
    [FactureReservationStatusEnum.FACTURABLE]: {
        id: FactureReservationStatusEnum.FACTURABLE,
        label: 'Facturée',
        class: 'success',
    },
    [FactureReservationStatusEnum.NON_FACTURABLE]: {
        id: FactureReservationStatusEnum.NON_FACTURABLE,
        label: 'Non Facturée',
        class: 'danger',
    },
};
export const CPFoffres = {
    manual: {
        1: { id: 1, name: 'FORFAIT 6 HEURES' },
        2: { id: 2, name: 'FORFAIT ACCÉLÉRÉ 12 HEURES' },
        3: { id: 3, name: 'FORFAIT ACCÉLÉRÉ 22 HEURES' },
        4: { id: 4, name: 'FORFAIT ACCÉLÉRÉ 27 HEURES' },
        5: { id: 5, name: 'FORFAIT ACCÉLÉRÉ 32 HEURES' },
        6: { id: 6, name: 'FORFAIT ACCÉLÉRÉ 37 HEURES' },
        7: { id: 7, name: 'FORFAIT ACCÉLÉRÉ 42 HEURES' },
    },
    auto: {
        8: { id: 8, name: 'FORFAIT 7 HEURES' },
        9: { id: 9, name: 'FORFAIT ACCÉLÉRÉ 15 HEURES' },
        10: { id: 10, name: 'FORFAIT ACCÉLÉRÉ 22 HEURES' },
        11: { id: 11, name: 'FORFAIT ACCÉLÉRÉ 27 HEURES' },
        12: { id: 12, name: 'FORFAIT ACCÉLÉRÉ 32 HEURES' },
        13: { id: 13, name: 'FORFAIT ACCÉLÉRÉ 37 HEURES' },
    },
};
