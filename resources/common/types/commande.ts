import type { StudentType } from './user';
import type { CartType } from './cart';
import { PaymentStatusEnum, PaymentTypeEnum } from '@common/enums';

export type CommandeType = {
    id: string;
    student_id: string;
    cart_id: string;
    reference: string;
    payment_id: string;
    payment_status: PaymentStatusEnum;
    payment_method: PaymentTypeEnum;
    amount: number;
    balance: number;
    deleted_at: string | null;
    created_at: string;
    updated_at: string;
    student: StudentType;
    cart: CartType;
};
