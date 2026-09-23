import { PlusIcon } from '@adersolutions/icons';
import { routes } from '@espace-admin/routes';

export const headings = [
    { name: 'Offre', className: '!pl-[52px]' },
    { name: 'Prix Criel' },
    { name: 'Balance Criel' },
    { name: 'Prix Toulouse' },
    { name: 'Balance Toulouse' },
    { name: "Prix d'origine" },
    { name: 'Remise' },
    { name: 'Tranche', className: 'text-center' },
];
export const tabs = [
    { name: 'Actif', id: '' },
    { name: 'Archivé', id: '0' },
    { name: 'CPF', id: '2', key: 'is_cpf' },
    { name: 'Pannier', id: '1', key: 'is_cart' },
];

export const actions = [
    {
        label: 'Nouvelle offre',
        variant: 'primary',
        icon: PlusIcon,
        href: route(routes.shop.offers.create),
    },
];
