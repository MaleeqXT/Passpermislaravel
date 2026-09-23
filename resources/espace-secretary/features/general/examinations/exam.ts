import { EditIcon } from '@adersolutions/icons';
import { routes } from '@espace-secretary/routes';

export const tabs = [
    { name: 'Liste d’attente', id: 1 },
    { name: 'Pré-liste', id: 2 },
];
export const headings = [
    { name: 'Candidat' },
    { name: 'Téléphone' },
    { name: 'Boite' },
    { name: 'Date d\'examen' },
    { name: 'Dernier heure' },
    { name: 'Statut' },
    { name: 'Résultat' },
    { name: 'Commentaire' },
];

export const bulkActions = [
    {
        title: 'Edit',
        variant: 'secondary',
        isLink: true,
        icon: EditIcon,
        onAction: (item) => route(routes.exams.update, item.id),
    },
];

export const getReservation = (item) => {
    if (item.student?.trainings?.length) {
        return item.student?.trainings[0]?.reservation || {};
    }
    return {};
};
