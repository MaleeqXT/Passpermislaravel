import { PlusIcon, LinkIcon, NoteIcon, UndoIcon, ArchiveIcon, ChartHistogramLastIcon } from '@adersolutions/icons';
import { routes } from '@espace-admin/routes';
import { GeneralStatusEnum } from '@common/enums';
import { UserType } from '@common/types';
import { BulkActionType } from '@shared/types';
import axios from 'axios';

export const actions = [
    {
        label: 'Nouveau Candidat',
        variant: 'primary',
        icon: PlusIcon,
        href: route(routes.users.students.create),
    },
    {
        label: 'Exporter Excel',
        variant: 'secondary',
        icon: ChartHistogramLastIcon,
        async onClick() {
            try {
                const response = await axios.get('/admin/users/students/export-all', { responseType: 'blob' });
                const url = window.URL.createObjectURL(new Blob([response.data]));
                const link = document.createElement('a');
                link.href = url;
                link.setAttribute('download', `students-sales-all.xlsx`);
                document.body.appendChild(link);
                link.click();
                link.remove();
                window.URL.revokeObjectURL(url);
                alert('✅ Export complété avec succès');
            } catch (error) {
                console.error('Export error:', error.response || error);
                alert('❌ Erreur lors de l\'export des données.');
            }
        },
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

export const bulkActions: (item: Required<UserType>) => BulkActionType[] = (item: Required<UserType>) => [
    {
        title: 'Details',
        icon: NoteIcon,
        href: route(routes.users.students.general, { student: item.student?.id }),
    },
    {
        title: 'Connecter',
        icon: LinkIcon,
        self: true,
        href: route(routes.impersonate.start, { user: item.id }),
    },
    {
        title: 'Exporter Excel',
        icon: ChartHistogramLastIcon,
        async onAction() {
            try {
                const response = await axios.get(`/admin/users/students/${item.student?.id}/export`, { responseType: 'blob' });
                const url = window.URL.createObjectURL(new Blob([response.data]));
                const link = document.createElement('a');
                link.href = url;
                link.setAttribute('download', `student-${item.id}-sales.xlsx`);
                document.body.appendChild(link);
                link.click();
                link.remove();
                window.URL.revokeObjectURL(url);
                alert('✅ Export du candidat complété avec succès');
            } catch (error) {
                console.error('Export error:', error.response || error);
                alert('❌ Erreur lors de l\'export du candidat.');
            }
        },
    },

    {
        title: 'Contrat',
        icon: NoteIcon,
        isLink: true,
        external: true,
        onAction() {
            const fallback = `/admin/users/students/${item.student?.id}/contract`;

            const url =
                item.student?.contract_path ||
                ((): string => {
                    try {
                        return route(routes.users.students.contract, { student: item.student?.id });
                    } catch {
                        return fallback;
                    }
                })();

            if (!url) {
                alert('❌ Aucun contrat disponible pour ce candidat.');
                return '';
            }

            return url;
        },
    },

    {
        label: 'Désarchivé',
        icon: ArchiveIcon,
        variant: 'secondary',
        confirm: {
            message: 'Voulez-vous vraiment supprimer ce candidat ?',
            async onConfirm() {
                try {
                    const response = await axios.delete(`/admin/users/students/${item.student?.id}`);

                    if (response.status === 200 || response.data.success) {
                        alert('✅ Candidat supprimé avec succès');
                        window.location.reload();
                    } else {
                        alert('❌ Erreur lors de la suppression');
                    }
                } catch (error) {
                    console.error('Delete error:', error.response || error);
                    alert('Une erreur est survenue lors de la suppression.');
                }
            },
        },
    },

    item.status == GeneralStatusEnum.ACTIVE
        ? {
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
          }
        : {
              label: 'Restore',
              icon: UndoIcon,
              variant: 'danger',
              confirm: {
                  url: routes.users.user.archive,
                  message: 'Etes-vous sûr que vous voulez restaurer Candidat',
                  payload: {
                      status: GeneralStatusEnum.ACTIVE,
                  },
              },
          },
];
