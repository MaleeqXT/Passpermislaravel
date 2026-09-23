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
    <PageMobile v-bind="attrs" title="Titre Pro ECSR">
        <DocumentsPersonelSection :items="data?.media || []" key-name="media_diplom">
            <p>BEPECASER ou Titre Professionnel de la conduite</p>
        </DocumentsPersonelSection>
    </PageMobile>
</template>
