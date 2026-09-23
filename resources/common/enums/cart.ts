import type { GlobalListType } from '@common/types';

// Enum for Cart Status IDs
export enum CartStatusEnum {
    PENDING = 1,
    PAID,
    // REFUNDED,
    // ABANDONED,
    // EXPIRED,
}

export const CartStatus: GlobalListType<CartStatusEnum> = {
    [CartStatusEnum.PENDING]: {
        id: CartStatusEnum.PENDING,
        name: 'En attente',
        class: 'info', // Info class for neutral or informational state
    },
    [CartStatusEnum.PAID]: {
        id: CartStatusEnum.PAID,
        name: 'Payé',
        class: 'success', // Success class for positive outcomes
    },
    // [CartStatusEnum.CANCELLED]: {
    //     id: CartStatusEnum.CANCELLED,
    //     name: 'Annulé',
    //     class: 'danger', // Danger class for failure or critical issues
    // },
    // [CartStatusEnum.REFUNDED]: {
    //     id: CartStatusEnum.REFUNDED,
    //     name: 'Remboursé',
    //     class: 'warning', // Warning class for actions that are in progress
    // },
    // [CartStatusEnum.ABANDONED]: {
    //     id: CartStatusEnum.ABANDONED,
    //     name: 'Abandonné',
    //     class: 'dark', // Dark class for minimalistic or neutral styles
    // },
    // [CartStatusEnum.EXPIRED]: {
    //     id: CartStatusEnum.EXPIRED,
    //     name: 'Expiré',
    //     class: 'default', // Default class for statuses that don't fit in other categories
    // },
};
