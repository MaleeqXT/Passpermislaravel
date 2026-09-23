import { PlusIcon, LinkIcon, NoteIcon, UndoIcon, ArchiveIcon } from '@adersolutions/icons';
import { routes } from '@espace-secretary/routes';
import { GeneralStatusEnum } from '@common/enums';
import { UserType } from '@common/types';
import { BulkActionType } from '@shared/types';
import { useApp } from '@shared/stores';

export const actions = [
    {
        label: 'Nouveau Candidat',
        variant: 'primary',
        icon: PlusIcon,
        href: route(routes.users.students.create),
    },
];
export const tabs = [
    { name: 'Tous', id: '3' },
    { name: 'Actif', id: '1' },
    { name: 'Archivé', id: '2' },
    { name: 'Nouveau', id: 'true', key: 'new' },
];
export const headings = [
    { name: 'Candidat' },
    { name: 'Balance Disponible' },
    { name: 'Lieux', className: 'text-center' },
    { name: "Date d'inscription" },

];

export const bulkActions: (selectedItems: Required<UserType>) => BulkActionType[] = (item: Required<UserType>) => {
    // Access current logged user from the app store and determine admin status
    const { user } = useApp();
    const u: any = user || {};
    const isAdmin = (() => {
        if (!u) return false;
        if (Array.isArray(u.roles) && u.roles.some((r: any) => (r.name || r) === 'admin')) return true;
        if (u.role && ((u.role.name && u.role.name.toLowerCase() === 'admin') || u.role_id === 1)) return true;
        if (u.role_id === 1 || u.role === 1) return true;
        return false;
    })();

    const actions: BulkActionType[] = [];

    // Details button for both admin and secretary
    actions.push({
        title: 'Details',
        icon: NoteIcon,
        href: route(routes.users.students.general, item.student.id),
    });

    actions.push({
        title: 'Connecter',
        icon: LinkIcon,
        self: true,
        href: route(routes.impersonate.start, item.id || '-'),
    });

    // {
    //     title: 'Ajouter une Offre',
    //     onAction: () => handleActivePanel(item),
    // },

    if (item.status == GeneralStatusEnum.ACTIVE) {
        actions.push({
            label: 'Archivé',
            icon: ArchiveIcon,
            variant: 'danger',
            confirm: {
                url: routes.users.user.archive,
                message: 'Etes-vous sûr que vous voulez archiver Candidat',
                payload: {
                    status: GeneralStatusEnum.INACTIVE,
                },
            },
        });
    } else {
        actions.push({
            label: 'restore',
            icon: UndoIcon,
            variant: 'danger',
            confirm: {
                url: routes.users.user.archive,
                message: 'Etes-vous sûr que vous voulez restaurer Candidat',
                payload: {
                    status: GeneralStatusEnum.ACTIVE,
                },
            },
        });
    }

    return actions;
};
