import { ArrowDownIcon, ArrowUpIcon } from '@adersolutions/icons';
import { PaymentStatus } from '@common/enums';
import { moneyFormat } from '@shared/utils';

export const headings = [
    { name: 'Ref' },
    { name: 'Date création' },
    { name: 'Candidat' },
    { name: 'Total' },
    { name: 'Statut' },
    { name: 'Marchandises' },
];

export const tabs = [
    {
        id: '',
        name: 'Tous',
    },
    ...Object.values(PaymentStatus),
];

export type CommandesStatsType = {
    current: {
        pending_sales: number;
        paid_sales: number;
        refunded_sales: number;
        canceled_sales: number;

        paid_revenue: number;
        pending_revenue: number;
        refunded_revenue: number;
        canceled_revenue: number;
    };
    comparison: {
        paid_revenue: {
            value: number;
            perecent: number;
        };
        pending_revenue: {
            value: number;
            perecent: number;
        };
        refunded_revenue: {
            value: number;
            perecent: number;
        };
        canceled_revenue: {
            value: number;
            perecent: number;
        };
        paid_sales: {
            value: number;
            perecent: number;
        };
        pending_sales: {
            value: number;
            perecent: number;
        };
        refunded_sales: {
            value: number;
            perecent: number;
        };
        canceled_sales: {
            value: number;
            perecent: number;
        };
    };
    // weekly: {
    //     date: string;
    //     total_sales: number;
    // }[];
};
type ComparisonKeys = keyof CommandesStatsType['comparison'];

export const getStateVariant = (comparisons: CommandesStatsType['comparison'], days: string): string => {
    const comparison = {} as any;
    for (const key in comparisons) {
        const el = comparisons[key as ComparisonKeys];
        const isCanceledOrRefunded = key.includes('canceled') || key.includes('refunded');

        if (el.perecent !== undefined) {
            let obj = {
                icon: null,
                color: 'bg-gray-500/30',
            };
            if (el.perecent !== 0) {
                obj = isCanceledOrRefunded
                    ? {
                          icon: el.perecent > 0 ? ArrowDownIcon : ArrowUpIcon,
                          color: el.perecent > 0 ? 'bg-red-600' : 'bg-green-600',
                      }
                    : {
                          icon: el.perecent > 0 ? ArrowUpIcon : ArrowDownIcon,
                          color: el.perecent > 0 ? 'bg-green-600' : 'bg-red-600',
                      };
            }
            comparison[key as ComparisonKeys] = {
                text: `${(el.value > 0 && '+') || ''}${moneyFormat(el.value || 0)} vs les ${days}`,
                ...obj,
            };
        } else {
            comparison[key as ComparisonKeys] = {
                icon: null,
                color: el.value > 0 ? 'bg-info-600' : el.value ? 'bg-yellow-600' : 'bg-gray-500/30',
                text: `${(el.value > 0 && '+') || ''}${el.value || 0} commandes par rapport aux ${days}`,
            };
        }
    }
    return comparison;
};
