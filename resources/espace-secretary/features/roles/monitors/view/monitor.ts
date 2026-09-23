import { routes } from '@espace-admin/routes';
import { BillIcon, NoteIcon, IdentityCardIcon } from '@adersolutions/icons';
// import moment from 'moment-timezone';

export const pagesNavigation = (id = '-') => [
    {
        icon: IdentityCardIcon,
        path: route(routes.users.monitors.edit, id),
        pathname: '/edit',
        title: 'Coordonnées et identité',
    },
    {
        icon: BillIcon,
        path: route(routes.users.monitors.invoices, id),
        pathname: '/factures',
        title: 'Factures et règlements',
    },
    {
        icon: NoteIcon,
        path: route(routes.users.monitors.fich.documents, id),
        pathname: '/documents',
        title: 'Pièces jointes',
    },
    // {
    //     icon: NoteIcon,
    //     path: route(routes.users.monitors.fich.documents, id),
    //     pathname: '/docs-professional',
    //     title: 'Documents professionneles',
    // },
    // {
    //     icon: NoteIcon,
    //     path: route(routes.users.monitors.fich.coordonneesBancaire, id),
    //     pathname: '/coordonnees-bancaire',
    //     title: 'Coordonnées bancaires',
    // },
];
