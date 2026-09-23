import { routes } from '@espace-secretary/routes';
import { CalendarIcon, CashDollarIcon, FileIcon, GaugeIcon, LocationIcon, NoteIcon, ProductIcon, StoreIcon } from '@adersolutions/icons';
import type { NavigationItemType } from '@shared/types';

// Type definition for NavigationItemType

// helper for safely resolving route names that might not exist
const resolveRoute = (path?: string): string => {
    if (!path) {
        return '#';
    }
    try {
        return route(path);
    } catch {
        return '#';
    }
};

// Table of Management items


const tableOfMangement: NavigationItemType[] = [
    {
        name: 'Préférences',
        divider: true,
    },
    {
        name: 'Compétences',
        icon: NoteIcon,
        href: route(routes.settings.competences.group.index),
    },
    {
        name: 'Localisations',
        icon: LocationIcon,
        href: route(routes.settings.locations.area.index),
    },
];

// Main navigation structure
export const navigation: NavigationItemType[] = [

   { name: 'Dashboard',
     href: route('secretary.dashboard.index'),
      icon: GaugeIcon, },

    {
        name: 'Séances',
        icon: CalendarIcon,
        href: route(routes.reservations.main.index),
        children: [
            { name: 'Annulations', href: route(routes.cancellations.index) },
            { name: 'Propositions', href: route(routes.propositions.index) },
        ],
    },
    {
        name: 'Commandes',
        href: route(routes.shop.commandes.index),
        icon: StoreIcon,
        children: [
            { name: 'Paniers', href: route(routes.shop.cart.index) },
            { name: 'Offres', href: route(routes.shop.offers.index) },
        ],
    },
    {
        name: 'Examens',
        icon: FileIcon,
        href: route(routes.exams.index),
    },
    // {
    //     name: 'CPF',
    //     icon: ProductIcon,
    //     href: route(routes.cpf.index),
    // },
    {
        name: 'Facturation',
        href: route(routes.invoices.index),
        mobile: true,
        icon: CashDollarIcon,
    },
    {
        name: 'Form CPF',
        icon: ProductIcon,
        href: route(routes.formCpf.index),
    },
   
    {
        name: 'Utilisateurs',
        divider: true,
    },

    { name: 'Moniteurs', href: route(routes.users.monitors.index), letter: true },
    { name: 'Candidats', href: route(routes.users.students.index), letter: true },

    ...tableOfMangement,
];

// Drawer and Header navigation
export const headerNavigation: NavigationItemType[] = [
    { name: 'Site en ligne ', letter: true, href: route(routes.pages.home.index), sticky: true },
];

export const drawerNavigation: NavigationItemType[] = [
    {
        name: 'preferences',
        children: tableOfMangement.filter((item) => !item.divider),
    },

];
