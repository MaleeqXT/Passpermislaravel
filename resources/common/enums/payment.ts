// Enum for Payment Status
export enum PaymentStatusEnum {
    PENDING = 1,
    PAID,
    REFUNDED,
    CANCELED,
    // PARTIAL_REFUND,
}

// Enum for Payment Types
export enum PaymentTypeEnum {
    STRIPE = 1,
    PAYPAL,
    // CASH,
    // TRANSFER,
    // CHEQUE,
}

// Payment Type
export const PaymentType = {
    [PaymentTypeEnum.STRIPE]: {
        id: PaymentTypeEnum.STRIPE,
        name: 'Stripe',
        class: 'info',
    },
    [PaymentTypeEnum.PAYPAL]: {
        id: PaymentTypeEnum.PAYPAL,
        name: 'Paypal',
        class: 'success',
    },
    // [PaymentTypeEnum.CASH]: {
    //     id: PaymentTypeEnum.CASH,
    //     name: 'Espèce',
    //     class: 'warning',
    // },
    // [PaymentTypeEnum.TRANSFER]: {
    //     id: PaymentTypeEnum.TRANSFER,
    //     name: 'Virement',
    //     class: 'primary',
    // },
    // [PaymentTypeEnum.CHEQUE]: {
    //     id: PaymentTypeEnum.CHEQUE,
    //     name: 'Chèque',
    //     class: 'danger',
    // },
};

// Payment Status
export const PaymentStatus = {
    [PaymentStatusEnum.PENDING]: {
        id: PaymentStatusEnum.PENDING,
        name: 'En attente',
        class: 'secondary',
    },
    [PaymentStatusEnum.PAID]: {
        id: PaymentStatusEnum.PAID,
        name: 'Payé',
        class: 'success',
    },
    [PaymentStatusEnum.REFUNDED]: {
        id: PaymentStatusEnum.REFUNDED,
        name: 'Remboursé',
        class: 'info',
    },
    [PaymentStatusEnum.CANCELED]: {
        id: PaymentStatusEnum.CANCELED,
        name: 'Annulé',
        class: 'danger',
    },
};
