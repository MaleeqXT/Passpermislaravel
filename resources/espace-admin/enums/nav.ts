import { routes } from '@espace-admin/routes';
import { CalendarIcon, CashDollarIcon, FileIcon, GaugeIcon, LocationIcon, NoteIcon, ProductIcon, StoreIcon } from '@adersolutions/icons';
import type { NavigationItemType } from '@shared/types';

// Type definition for NavigationItemType

// helper that safely resolves a route string and avoids undefined errors
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
     href: route(routes.dashboard.index),
      icon: GaugeIcon, },
    // Top-level quick access to the unrestricted reservations page
    // {
    //     name: 'Séances (Sans restriction)',
    //     icon: CalendarIcon,
    //     href: route(routes.reservations.unrestricted.index),
    // },

    {
        name: 'Séances',
        icon: CalendarIcon,
        href: route(routes.reservations.main.index),
        children: [
            { name: 'Annulations', href: route(routes.cancellations.index) },
            { name: 'Propositions', href: route(routes.propositions.index) },
            // { name: 'Séances (Sans restriction)', href: route(routes.reservations.unrestricted.index) },
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
        name: 'Rapport Étudiants Actifs',
        icon: FileIcon,
        href: resolveRoute(routes.reports?.activeStudents?.index),
    },

    {
        name: 'Utilisateurs',
        divider: true,
    },
    { name: 'Administrations', href: route(routes.users.admins.index), letter: true },
    { name: 'Secrétaires', href: route('secretaries.index'), letter: true },
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
