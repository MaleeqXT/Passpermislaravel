/**
 * Formats a numeric value as currency with a specified symbol.
 * @param value - The numeric value to format.
 * @param symbol - The currency symbol to append (default is " Dh").
 * @returns A formatted currency string.
 */
export const moneyFormat = (value: number): string => {
    return new Intl.NumberFormat('fr', {
        style: 'currency',
        currency: 'EUR',
        currencyDisplay: 'symbol',
    }).format(value || 0); //+ symbol
};
