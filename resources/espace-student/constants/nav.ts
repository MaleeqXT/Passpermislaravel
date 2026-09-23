import { routes } from '@espace-student/routes';
import { CalendarIcon, HomeIcon, HomeFilledIcon } from '@adersolutions/icons';
import { NavigationItemType } from '@shared/types';

export const navigation = [
    { name: 'Accueil', href: route(routes.dashboard.index), filled: HomeFilledIcon, icon: HomeIcon },
    { name: 'Mes séances ', href: route(routes.reservations.index), filled: CalendarIcon, icon: CalendarIcon },
];

export const drawerNavigation: NavigationItemType[] = [
    // {
    //     name: 'Informations personnelles',
    //     children: [],
    // },

    {
        name: 'Options',
        children: [
            { name: 'Examen info', href: route(routes.settings.neph.index) },
            { name: 'Offres', href: route(routes.shop.index) },
            { name: 'Mes achats', href: route(routes.commandes.index) },
            { name: 'Mes séances annulées', href: route(routes.cancellations.index) },
            // { name: 'Les CPFs', href: route(routes.cpf.index) },
            // { name: 'Mentions légales', href: route(routes.cancellations.index) },
            // { name: 'Contactez-nous', href: route(routes.cancellations.index) },
        ],
    },
];
