import type { MediaType } from '@shared/types';
import type { GlobalDataType } from './common';
import { OffreCategoryEnum, OffreTypeEnum } from '@common/enums';

export type OfferType = Required<GlobalDataType> & {
    price_ht: number | null;
    is_cpf: boolean;
    is_offer_cart: boolean;
    description: string;
    caracteristiques: string;
    original_price: number;
    discounted_price: number | null;
    second_price: number | null;   // ✅ new field
    final_price: number;
    promo: string;
    balance: number;
    balance_2: number | null;      // ✅ new field
    is_auto: boolean;
    multi_payment: number;
    agency_name: string | null;    // ✅ new field
    type: OffreCategoryEnum;
    type_offre: OffreTypeEnum;
    color: string;
    media: MediaType;
};
