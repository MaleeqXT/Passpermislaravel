import { CartDetailType, OfferType } from '@common/types';

// Calculate the total price of offers in the list
export const getCartTotal = (items: CartDetailType[]): number => {
    let totalPrice = 0;
    if (items) {
        items.forEach((item) => {
            totalPrice += parseInt(item.offer?.final_price ?? 0);
        });
    }
    return totalPrice;
};

// Calculate the total balance quantity in the list
export const getTotalBalance = (items: CartDetailType[]): number => {
    let balanceTotal = 0;
    if (items) {
        items.forEach((item) => {
            balanceTotal += item.offer?.balance ?? 0;
        });
    }
    return balanceTotal;
};
