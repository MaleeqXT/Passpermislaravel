<script setup lang="ts">
import { Card, DateField, EmptyState, Errors, FileLibrary, ButtonGroup, InputField, Back, TabSwitch, Alerts } from '@shared/components';
import { useForm } from '@inertiajs/vue3';
import { useFileLibrary, useAlert } from '@shared/stores';
import { computed } from 'vue';
import { routes } from '@espace-admin/routes';
import { DocumentItem } from '../partials';
import { NoteIcon, PlusIcon } from '@adersolutions/icons';
import { router } from '@inertiajs/vue3';
import { fileTypeGroups } from '@shared/enums';
import { useFiles } from '@shared/hooks';
import ViewContainer from './ViewContainer.vue';
import { reactive } from 'vue';
import type { MonitorType, IdentityDocumentType, PermitDocumentType, DiplomaDocumentType, ProDocumentType } from '@common/types';
import type { ButtonType, MediaType } from '@shared/types';
// @todo
// import { formJuridiqueItems } from '@espace-monitor/features/settings/documentsProfessionel/documentsPersonel';
type PropsType = {
    monitor: MonitorType;
    pieceIdentite: IdentityDocumentType;
    permis: PermitDocumentType;
    diplom: DiplomaDocumentType;
    professionel: ProDocumentType;
};

const props = defineProps<PropsType>();

const fl = useFileLibrary();
const alert = useAlert();
const files = useFiles();
interface FormErrors {
    [key: string]: any;
    media_piece_identite: MediaType[];
    media_permis: MediaType[];
    media_diplom: MediaType[];
    denomination_social: string;
    forme_juridique: string;
    siret: string;
    num_autorisation: string;
    date_creation: string;
    autorisations: {
        media: MediaType[];
        autorisation: string;
        visite: string;
        // attestation_vigilance: string[];
    };
}
const tabs = [
    { name: 'Documents personnels', id: 0 },
    { name: 'Documents professionnels', id: 1 },
];
const state = reactive({
    tab: tabs[0].id,
});
const form = useForm<FormErrors>({
    media_piece_identite: props.pieceIdentite?.media || [],
    media_permis: props.permis?.media || [],
    media_diplom: props.diplom?.media || [],

    denomination_social: props.professionel?.denomination_social,
    forme_juridique: props.professionel?.forme_juridique,
    siret: props.professionel?.siret,
    num_autorisation: props.professionel?.num_autorisation,
    date_creation: props.professionel?.date_creation,
    autorisations: {
        media:
            props.professionel?.instructor_permission?.media?.map((v) => ({
                ...v.storage_media,
                media_id: v.id,
            })) || [],
        // attestation_vigilance:
        //     props.professionel?.instructor_permission?.attestation_vigilance?.map((v) => ({
        //         ...v.storage_media,
        //         media_id: v.id,
        //     })) || [],
        autorisation: props.professionel?.instructor_permission?.autorisation || null,
        visite: props.professionel?.instructor_permission?.visite || null,
    },
});

const submit = () => {
    form.transform((d: any) => {
        d.media_piece_identite = d.media_piece_identite.map((m: MediaType) => m.storage_media_id || m.id);
        d.media_permis = d.media_permis.map((m: MediaType) => m.storage_media_id || m.id);
        d.media_diplom = d.media_diplom.map((m: MediaType) => m.storage_media_id || m.id);
        d.autorisations = {
            media: d.autorisations.media.map((m: MediaType) => m.storage_media_id || m.id),
            autorisation: d.autorisations.autorisation,
            visite: d.autorisations.visite,
        };
        return d;
    });
    form.put(route(routes.users.monitors.fich.documentsUpdate, props.monitor?.id), {
        preserveScroll: true,
    });
};

const onStoreFile = (value) => {
    // form.media = $event
    if (fl.source) {
        if (fl.source === 'professionel') {
            if (form.autorisations.media.some((item) => item.id === value.id)) {
                alert.show({
                    title: 'Ce document a déjà été ajouté',
                    type: 'error',
                });
            } else {
                form.autorisations.media.push(value);
            }
            return;
        }
        // if (fl.source === 'attestation_vigilance') {
        //     if (form.autorisations.attestation_vigilance.some((item) => item.id === value.id)) {
        //         alert.show({
        //             title: 'Ce document a déjà été ajouté',
        //             type: 'error',
        //         });
        //     } else {
        //         form.autorisations.attestation_vigilance.push(value);
        //     }
        //     return;
        // }
        if (form[fl.source].some((item) => item.id === value.id)) {
            alert.show({
                title: 'Ce document a déjà été ajouté',
                type: 'error',
            });
        } else {
            form[fl.source] = [...form[fl.source], value];
        }
    }
};
const onDeleteMedia = (item, key) => {
    if (item?.created_at) {
        files.delete(item.storage_media_id).then(() => {
            form[key] = form[key].filter((media) => media.id !== item.id);
            router.reload();
            form.defaults();
        });
    } else {
        form[key] = form[key].filter((media) => media.id !== item.id);
    }
};

const onDeleteMedia2 = (item) => {
    if (item?.created_at) {
        files.delete(item.storage_media_id).then(() => {
            form.autorisations.media = form.autorisations.media.filter((media) => media.id !== item.id);
            router.reload();
        });
    } else {
        form.autorisations.media = form.autorisations.media.filter((media) => media.id !== item.id);
    }
};

// const onDeleteMedia3 = (item) => {
//     if (item?.created_at) {
//         files.delete(item.storage_media_id).then(() => {
//             form.autorisations.attestation_vigilance = form.autorisations.attestation_vigilance.filter((media) => media.id !== item.id);
//             router.reload();
//         });
//     } else {
//         form.autorisations.attestation_vigilance = form.autorisations.attestation_vigilance.filter((media) => media.id !== item.id);
//     }
// };

const actions = computed<ButtonType[]>(() => [
    {
        label: 'Enregister les documents',
        variant: 'primary',
        disabled: !form.isDirty,
        loading: form.processing,
        submit: true,
        onAction: submit,
    },
    {
        label: 'Rejeter',
        disabled: form.processing || !form.isDirty,
        variant: 'secondary',
        onAction: () => {
            form.reset();
            form.clearErrors();
        },
    },
]);

const action = (key, title = '') => ({
    label: title || 'Ajouter un document',
    variant: 'info',
    link: true,
    icon: PlusIcon,
    onAction: () => fl.open(key),
});
</script>

<template>
    <ViewContainer :monitor="monitor">
        <FileLibrary :types="fileTypeGroups.all" @submit="onStoreFile" />
        <section class="px-5 max-w-screen-md mx-auto pb-20">
            <Back :back="route(routes.users.monitors.index)" title="Pièces jointes" />
            <Alerts />

            <TabSwitch :items="tabs" v-model="state.tab" full size="lg" class="mb-5" />
            <article v-if="state.tab === tabs[1].id">
                <Card block padding title="Documents professionnel">
                    <div class="grid grid-cols-2 gap-5">
                        <InputField
                            v-model="form.denomination_social"
                            :error="form.errors.denomination_social"
                            label="Dénomination sociale"
                        />
                        <!-- <Select
                            v-model="form.forme_juridique"
                            label="Forme juridique"
                            :items="formJuridiqueItems"
                            :error="form.errors.forme_juridique"
                            input-class="bg-white"
                        /> -->
                        <DateField
                            v-model="form.date_creation"
                            :error="form.errors.date_creation"
                            label="Date de création d'entreprise"
                            name="date"
                        />
                        <InputField v-model="form.siret" :error="form.errors.siret" label="SIRET" />
                        <InputField
                            v-model="form.num_autorisation"
                            :error="form.errors.num_autorisation"
                            type="number"
                            class="appearance-none"
                            label="Numero d'autorisation d'enseigner"
                        />
                    </div>
                </Card>

                <Card
                    block
                    padding
                    title="Autorisation d'enseigner"
                    subtitle="Merci de fournir le recto & verso de ce document"
                    :action="action('professionel')"
                >
                    <div class="flex gap-5">
                        <DateField
                            v-model="form.autorisations.autorisation"
                            :error="form.errors['autorisations.autorisation']"
                            label="Date de autorisation"
                            name="date"
                        />
                        <DateField
                            v-model="form.autorisations.visite"
                            :error="form.errors['autorisations.visite']"
                            label="Date de visite"
                            name="date"
                        />
                    </div>
                    <ul class="flex flex-col gap-2 mt-5">
                        <li class="block font-medium text-xs text-gray-600">Document(s) téléchargé(s)</li>
                        <div class="space-y-1">
                            <DocumentItem
                                v-for="(item, index) in form.autorisations.media"
                                :key="item.id"
                                :index="index"
                                :item="item"
                                :deleting="files.loading[item.storage_media_id]"
                                @delete="onDeleteMedia2(item)"
                            />
                        </div>
                        <EmptyState
                            v-if="!form.autorisations.media.length"
                            title="Aucun document"
                            :image="NoteIcon"
                            class="py-5 bg-slate-50 rounded-xl"
                        >
                            Aucun document n'a été ajouté pour le moment.
                        </EmptyState>
                        <Errors :errors="form.errors['autorisations.media']" />
                    </ul>
                </Card>
                <!-- <Card block padding title="Attestation de vigilance" :action="action('attestation_vigilance')">
                    <ul class="flex flex-col gap-2 mt-5">
                        <li class="block font-medium text-xs text-gray-600">Document(s) téléchargé(s)</li>
                        <DocumentItem
                            v-for="(item, index) in form.autorisations.attestation_vigilance"
                            :key="item.id"
                            :index="index"
                            :item="item"
                            :deleting="files.loading[item.storage_media_id]"
                            @delete="onDeleteMedia3(item)"
                        />
                        <EmptyState
                            v-if="!form.autorisations.attestation_vigilance.length"
                            title="Aucun document"
                            :image="NoteIcon"
                            class="py-5 bg-slate-50 rounded-xl"
                        >
                            Aucun document n'a été ajouté pour le moment.
                        </EmptyState>
                        <Errors :errors="form.errors['autorisations.attestation_vigilance']" />
                    </ul>
                </Card> -->
            </article>
            <article v-else>
                <Card
                    block
                    padding
                    title="Pièce d'identité"
                    :subtitle="`${pieceIdentite?.media?.length || 0} document(s) d'identité.`"
                    :action="action('media_piece_identite')"
                >
                    <ul class="list-disc list-inside mt-2">
                        <li>la Carte d'identité recto & verso</li>
                        <li>le passeport</li>
                    </ul>
                    <div class="space-y-1">
                        <DocumentItem
                            v-for="item in form.media_piece_identite"
                            :key="item.id"
                            :item="item.storage_media || item"
                            :deleting="files.loading[item.storage_media_id]"
                            @delete="onDeleteMedia(item, 'media_piece_identite')"
                        />
                    </div>
                    <EmptyState
                        v-if="!form.media_piece_identite.length"
                        title="Aucun document"
                        :image="NoteIcon"
                        class="py-5 bg-slate-50 rounded-xl"
                    >
                        Aucun permis de conduire n'a été ajouté pour le moment.
                    </EmptyState>
                </Card>
                <Card
                    block
                    padding
                    title="Permis de Conduire"
                    subtitle="Merci de fournir le recto & verso de ce document"
                    :action="action('media_permis')"
                >
                    <div class="space-y-1">
                        <DocumentItem
                            v-for="item in form.media_permis"
                            :key="item.id"
                            :item="item.storage_media || item"
                            :deleting="files.loading[item.storage_media_id]"
                            @delete="onDeleteMedia(item, 'media_permis')"
                        />
                    </div>
                    <EmptyState
                        v-if="!form.media_permis.length"
                        title="Aucun document"
                        :image="NoteIcon"
                        class="py-5 bg-slate-50 rounded-xl"
                    >
                        Aucun permis de conduire n'a été ajouté pour le moment.
                    </EmptyState>
                </Card>
                <Card
                    block
                    padding
                    title="Diplôme d'enseignement | Titre Pro ECSR"
                    subtitle="BEPECASER ou Titre Professionnel de la conduite"
                    :action="action('media_diplom')"
                >
                    <div class="space-y-1">
                        <DocumentItem
                            v-for="item in form.media_diplom"
                            :key="item.id"
                            :item="item.storage_media || item"
                            :deleting="files.loading[item.storage_media_id]"
                            @delete="onDeleteMedia(item, 'media_diplom')"
                        />
                    </div>
                    <EmptyState
                        v-if="!form.media_diplom.length"
                        title="Aucun document"
                        :image="NoteIcon"
                        class="py-5 bg-slate-50 rounded-xl"
                    >
                        Aucun permis de conduire n'a été ajouté pour le moment.
                    </EmptyState>
                </Card>
            </article>
            <ButtonGroup :actions="actions" />
        </section>
    </ViewContainer>
</template>
