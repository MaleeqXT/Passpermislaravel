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
    <PageMobile title="Pièce d'identité" v-bind="attrs">
        <DocumentsPersonelSection :items="data?.media || []" key-name="media_piece_identite">
            <p>
                Vous devez fournir {{ data?.media?.length || 0 }} document(s) d'identité. <br />
                Nous acceptons:
            </p>
            <ul class="list-disc list-inside mt-2">
                <li>la Carte d'identité recto & verso</li>
                <li>le passeport</li>
            </ul>
        </DocumentsPersonelSection>
    </PageMobile>
</template>
