import { CartStatusEnum } from '@common/enums';
import type { OfferType } from './offer';
import type { StudentType } from './user';

export type CartType = {
    id: string;
    student_id: string;
    student?: StudentType;
    status: CartStatusEnum;
    deleted_at: string | null;
    created_at: string;
    updated_at: string;
    cart_details?: CartDetailType[];
    // when converted to a sale the related sale object may be attached
    sale?: {
        id: string;
        payment_status?: number;
        payment_method?: number;
        amount?: number;
        balance?: number;
        reference?: string;
    };
};

export type CartDetailType = {
    id: string;
    cart_id: string;
    offer_id: string;
    quantity: number;
    tranches: number;
    deleted_at: string | null;
    created_at: string;
    updated_at: string;
    offer?: OfferType;
};
