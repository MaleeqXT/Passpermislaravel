import { routes } from '@espace-monitor/routes';
import { useApp } from '@shared/stores';
import { NavigationItemType } from '@shared/types';
import {
    CalendarIcon,
    // CalendarFilledIcon,
    SettingsIcon,
    CashEuroIcon,
    HomeIcon,
    HomeFilledIcon,
    PersonExitIcon,
} from '@adersolutions/icons';

export const navigation = [
    {
        name: 'Accueil',
        icon: HomeIcon,
        filled: HomeFilledIcon,
        href: route(routes.dashboard.index),
    },
    {
        name: 'Séances',
        icon: CalendarIcon,
        filled: CalendarIcon,
        href: route(routes.reservations.index),
    },
    {
        name: 'Paramètres',
        icon: SettingsIcon,
        filled: SettingsIcon,
        drawer: true,
    },
];

export const drawerNavigation: NavigationItemType[] = [
    {
        name: 'Suivi et évaluation',
        children: [
            {
                name: 'Mes Candidats',
                icon: PersonExitIcon,
                filled: PersonExitIcon,
                href: route(routes.students.index),
            },
            {
                name: 'Mes séances proposées',
                icon: PersonExitIcon,
                filled: PersonExitIcon,
                href: route(routes.proposals.index),
            },
            {
                name: 'Mes séances annulées',
                href: route(routes.cancellations.index),
            },
        ],
    },
    {
        name: 'Paramètres',
        children: [
            {
                name: 'Localisations',
                href: route(routes.settings.places.index),
            },
            {
                name: 'Facturations',
                icon: CashEuroIcon,
                href: route(routes.invoices.menu),
                // external: true,
            },
            {
                name: 'Véhicules et documents',
                onAction: () => useApp().drawer.open('cars-menu'),
            },
        ],
    },
];
