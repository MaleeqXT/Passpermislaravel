import type { GlobalListType } from '@common/types';

// Enum for Cart Status IDs
export enum InvoiceStatusEnum {
    PENDING = 1,
    PAID,
}

export const InvoiceStatusList: GlobalListType<InvoiceStatusEnum> = {
    [InvoiceStatusEnum.PENDING]: {
        id: InvoiceStatusEnum.PENDING,
        name: 'En attente',
        class: 'default', // Info class for neutral or informational state
    },
    [InvoiceStatusEnum.PAID]: {
        id: InvoiceStatusEnum.PAID,
        name: 'Payé',
        class: 'success', // Success class for positive outcomes
    },
};
