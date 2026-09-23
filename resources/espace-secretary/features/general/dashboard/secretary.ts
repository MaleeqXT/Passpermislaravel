import { PlusIcon, LinkIcon, NoteIcon, UndoIcon, ArchiveIcon } from '@adersolutions/icons';
import { routes } from '@espace-secretary/routes';
import { GeneralStatusEnum } from '@common/enums';
import { UserType } from '@common/types';
import { BulkActionType } from '@shared/types';

export const actions = [
    {
        label: 'Nouveau Secrétaire',
        variant: 'primary',
        icon: PlusIcon,
         href: route('secretaries.create'),
    },
];
export const tabs = [
    { name: 'Tous', id: '3' },
    { name: 'Actif', id: '1' },
    { name: 'Archivé', id: '2' },
    { name: 'Nouveau', id: 'true', key: 'new' },
];
export const headings = [
    { name: 'Secrétaire' },
    { name: 'Email' },
    { name: 'Statut du rôle' },
    { name: "Date d'inscription" },
];


export const bulkActions: (selectedItems: Required<UserType>) => BulkActionType[] = (item: Required<UserType>) => [
  {
    title: 'Details',
    icon: NoteIcon,
    href: `/users/students/${item.student.id}`, // ✅ or your direct Laravel route
  },

  {
    title: 'Modifier',
    icon: NoteIcon,
    href: `/secretaries/${item.id}/edit`, // ✅ direct Laravel route
  },

  item.status == GeneralStatusEnum.ACTIVE
    ? {
        label: 'Archivé',
        icon: ArchiveIcon,
        variant: 'danger',
        confirm: {
          url: '/users/user/archive',
          message: 'Etes-vous sûr que vous voulez archiver ce Secrétaire ?',
          payload: { status: GeneralStatusEnum.INACTIVE },
        },
      }
    : {
        label: 'Restore',
        icon: UndoIcon,
        variant: 'info',
        confirm: {
          url: '/users/user/archive',
          message: 'Etes-vous sûr que vous voulez restaurer ce Secrétaire ?',
          payload: { status: GeneralStatusEnum.ACTIVE },
        },
      },
];

