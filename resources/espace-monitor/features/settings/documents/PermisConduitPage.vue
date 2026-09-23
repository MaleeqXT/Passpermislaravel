<script setup lang="ts">
import { PageMobile } from '@common/components';
import { DocumentsPersonelSection } from './partials';
import { useDocumentsPersonelMixin } from './DocumentsPage';

defineProps({
    data: Object,
});
const { attrs } = useDocumentsPersonelMixin();
</script>

<template>
    <PageMobile v-bind="attrs" title="Permis de Conduire">
        <DocumentsPersonelSection :items="data?.media || []" key-name="media_permis">
            <p>Merci de fournir le recto & verso de ce document</p>
        </DocumentsPersonelSection>
    </PageMobile>
</template>
