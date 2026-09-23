import { reactive } from 'vue';
import { useAlert, useApp } from '@shared/stores';
import { useForm } from '@inertiajs/vue3';
import { routes } from '@espace-monitor/routes';
import { useFiles } from '@shared/hooks';

export const useDocumentsProfessionel = (props) => {
    const medias = useFiles();
    const alert = useAlert();
    const { drawer } = useApp();
    const form = useForm({
        denomination_social: props.data?.denomination_social || null,
        forme_juridique: props.data?.forme_juridique || null,
        siret: props.data?.siret || null,
        num_autorisation: props.data?.num_autorisation || null,
        date_creation: props.data?.date_creation || null,
        autorisations: {
            media: props.data?.autorisation_enseigner?.media?.map((v) => ({ ...v.storage_media, media_id: v.id })) || [],
            autorisation: props.data?.autorisation_enseigner?.autorisation || null,
            visite: props.data?.autorisation_enseigner?.visite || null,
        },
    });

    const state = reactive({
        show: false,
        deleteMedia: null,
    });

    const onBack = () => {
        drawer.open('cars-menu');
        history.back();
    };

    const onSubmit = () => {
        form.transform((data) => {
            data.autorisations = {
                media: data.autorisations.media.map((media) => media.storage_media_id || media.id),
                autorisation: data.autorisations.autorisation,
                visite: data.autorisations.visite,
            };
            return data;
        });
        form.post(route(routes.settings.documentsProfessionnel.storeOrUpdate), {
            preserveScroll: true,
            onSuccess: () => {
                // onBack();
                state.show = false;
            },
        });
    };
    const onStoreFile = (value) => {
        // form.media = $event
        if (form.autorisations.media?.some((item) => item.id === value.id)) {
            alert.show({
                title: 'Ce document a déjà été ajouté',
                type: 'error',
            });
        } else {
            form.autorisations.media.push(value);
        }
    };
    const onDeleteConfirmed = (item = state.deleteMedia) => {
        if (state.deleteMedia) {
            medias.delete(item.media_id).then(() => {
                form.autorisations.media = form.autorisations.media.filter((media) => media.id !== item.id);
                state.deleteMedia = null;
                router.reload();
            });
        }
    };
    const onDelete = (item) => {
        if (item.created_at) {
            state.deleteMedia = item;
        } else {
            form.autorisations.media = form.autorisations.media.filter((media) => media.id !== item.id);
        }
    };
    return {
        onStoreFile,
        onDeleteConfirmed,
        onDelete,
        state,
        form,
        medias,
        onBack,
        onSubmit,
    };
};

export const formJuridiqueItems = [
    { id: 'EI', name: 'EI - Entreprise individuelle - sans TVA' },
    { id: 'SARL', name: 'SARL - Société à responsabilité limitée' },
    { id: 'SA', name: 'SA - Société anonyme' },
    { id: 'SAS', name: 'SAS - Société par actions simplifiée' },
    { id: 'SASU', name: 'SASU - Société par actions simplifiée unipersonnelle' },
    { id: 'SC', name: 'SC - Société civile' },
    { id: 'SCA', name: 'SCA - Société en commandite par actions' },
    { id: 'SCIC', name: "SCIC - Société coopérative d'intérêt collectif" },
    { id: 'SCM', name: 'SCM - Société civile de moyens' },
    { id: 'SCOP', name: 'SCOP - Société coopérative ouvrière de production' },
    { id: 'SCP', name: 'SCP - Société civile professionnelle' },
    { id: 'SCS', name: 'SCS - Société en commandite simple' },
    { id: 'SEL', name: "SEL - Société d'exercice libéral" },
    { id: 'SEP', name: 'SEP - Société en participation' },
    { id: 'SNC', name: 'SNC - Société en nom collectif' },
    { id: 'Société civile', name: 'Société civile' },
    { id: 'Société commerciale', name: 'Société commerciale' },
    { id: 'Société coopérative', name: 'Société coopérative' },
    { id: 'Société de capitaux', name: 'Société de capitaux' },
];
