<script setup lang="ts">
import { PageMobile } from '@common/components';
import { Card, DialogConfirm, DateField, Errors, Select, FileLibrary, InputField, EmptyState } from '@shared/components';
import { useDocumentsProfessionel, formJuridiqueItems } from '../documentsProfessionel/DocumentsPage';
import { PlusIcon } from '@adersolutions/icons';
import { DocumentProfessionelItem } from '../documentsProfessionel/partials';
import { useFileLibrary } from '@shared/stores';
import { fileTypeGroups } from '@shared/enums';

const props = defineProps({
    data: Object,
});

const fl = useFileLibrary();
const { onStoreFile, onDeleteConfirmed, onDelete, state, form, medias, onBack, onSubmit } = useDocumentsProfessionel(props);
</script>

<template>
    <PageMobile
        :back="onBack"
        slided
        title="Documents professionnels"
        :actions="[
            {
                label: 'Enregistrer',
                variant: 'warning',
                full: true,
                loading: form.processing,
                onAction: onSubmit,
            },
        ]"
    >
        <FileLibrary menu :types="fileTypeGroups.all" @submit="onStoreFile" />
        <Card title="Informations Société" class="mt-7 flex flex-col">
            <InputField v-model="form.denomination_social" :error="form.errors.denomination_social" label="Dénomination sociale" />
            <!-- <InputField v-model="form.forme_juridique" label="Form juridique" /> -->
            <Select
                v-model="form.forme_juridique"
                label="Forme juridique"
                :items="formJuridiqueItems"
                :error="form.errors.forme_juridique"
                input-class="bg-white"
            />
            <DateField v-model="form.date_creation" :error="form.errors.date_creation" label="Date de création d'entreprise" name="date" />
            <InputField v-model="form.siret" :error="form.errors.siret" label="SIRET" />
            <InputField v-model="form.num_autorisation" :error="form.errors.num_autorisation" label="Numero d'autorisation d'enseigner" />
        </Card>
        <div class="rainbow !h-px overflow-hidden mt-5 mb-4 bg-gray-200"></div>
        <Card
            title="Autorisation d'enseigner"
            class="flex flex-col"
            :action="{ icon: PlusIcon, link: true, onAction: () => fl.openMenu() }"
        >
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
            <li class="block font-medium text-xs mt-5 text-gray-700">Document(s) téléchargé(s)</li>
            <ul class="flex flex-col divide-y bg-white box overflow-clip">
                <DocumentProfessionelItem
                    v-for="(item, index) in form.autorisations.media"
                    :key="item.id"
                    :index="index"
                    :item="item"
                    @delete="onDelete(item)"
                />
                <EmptyState v-if="!form.autorisations.media.length" class="text-slate-700 py-10">
                    <p>Aucun document téléchargé</p>
                </EmptyState>
                <Errors :errors="form.errors['autorisations.media']" />
            </ul>
        </Card>

        <DialogConfirm
            :show="!!state.deleteMedia"
            :loading="medias.loading.deleting"
            empty-class="text-slate-700"
            @close="state.deleteMedia = null"
            @confirm="onDeleteConfirmed"
        >
            Etes-vous sûr que vous voulez supprimer ce document ?
        </DialogConfirm>
    </PageMobile>
</template>
