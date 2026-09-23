import { routes } from '@espace-admin/routes';
import {
    InfoIcon,
    CalendarCheckIcon,
    CalendarTimeIcon,
    ChatIcon,
    PaperCheckIcon,
    IdentityCardIcon,
    CatalogIcon,
    NoteIcon,
    CartIcon,
} from '@adersolutions/icons';
import moment from 'moment-timezone';

export const listDays = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];

export const pagesBadges = (user = {}) => [user.ville, user.postal].filter(Boolean)?.map((v) => ({ name: v, class: 'dark' }));

export const pagesNavigation = (id) => [
    // {
    //     icon: InfoIcon,
    //     path: route(routes.users.students.general, id),
    //     pathname: '/general',
    //     title: 'Général',
    //     subtitle: 'Informations générales',
    // },
    {
        icon: IdentityCardIcon,
        path: route(routes.users.students.edit, id),
        pathname: '/info',
        title: 'Informations et Document',
        subtitle: 'Gérez vos informations et documents',
    },
    {
        icon: CatalogIcon,
        path: route(routes.users.students.withZones, id),
        pathname: '/zones',
        title: 'Balance / Zone',
        subtitle: 'Détails des balance et zones',
    },
    {
        icon: PaperCheckIcon,
        path: route(routes.users.students.livret, id),
        pathname: '/livret',
        title: 'Compétences',
        subtitle: 'Suivi la progrès de candidat',
    },
    {
        icon: CartIcon,
        path: route(routes.users.students.cart, id),
        pathname: '/cart',
        title: 'Pannier Candidat',
        subtitle: 'Gerer les offres de pannier',
    },
    // {
    //     icon: NoteIcon,
    //     path: route(routes.users.students.cpf, id),
    //     pathname: '/cpf',
    //     title: 'CPF - Document',
    //     subtitle: 'Consultez les documents liés au CPF',
    // },
    {
        icon: NoteIcon,
        path: route(routes.users.students.docs, id),
        pathname: '/contrat-evaluation',
        title: 'Contrat / Evaluation',
        subtitle: 'Consultez les documents de Contrat ou evaluation',
    },
];

export const getDateWeekByIndex = (index = 0) => {
    const date = moment().add(index, 'week');
    return {
        start: date.startOf('isoWeek').format('YYYY-MM-DD'),
        end: date.endOf('isoWeek').format('YYYY-MM-DD'),
    };
};
export const getListMonitors = (list = []) =>
    list.reduce((acc, item) => {
        if (!acc.some((v) => v.id === item?.monitor?.id)) {
            acc.push({ ...item?.monitor, lieu: item.lieu });
        }
        return acc;
    }, []);
