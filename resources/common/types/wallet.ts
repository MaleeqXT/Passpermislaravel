// import type { GlobalDataType } from './common';
import type { OfferType } from './offer';

export type WalletType = {
    id: string;
    student_id: string;
    offer_id: string;
    balance: number;
    status: string;
    deleted_at: string | null;
    created_at: string;
    updated_at: string;
    cart_details_sum_quantity: number | null;
    offer: OfferType;
};

export type WalletOffreType = WalletType & {
    balance: number;
    name: string;
};
