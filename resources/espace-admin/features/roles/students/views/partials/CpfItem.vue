<script setup lang="ts">
// @todo
// import {
//     DAFFDownload,
//     DAHDownload,
//     DEFFDownload,
//     DqsDownload,
//     DSPDownload,
//     QEFDownload,
// } from '@espace-student/features/cpf/partials';
import { NoteIcon } from '@adersolutions/icons';
import { Card, InputField } from '@shared/components';
import { dateFormat } from '@shared/utils';
import moment from 'moment-timezone';

const props = defineProps({
    cpf: {
        type: Object,
        default: () => ({}),
    },
});

const docs = [
    {
        name: "Questionnaire d'entré en formation",
        key: 'document_questionnaire_entre_formation',
        // is: QEFDownload,
    },
    {
        name: "Attestation sur l'honneur",
        key: 'document_attestation_honneur',
        // is: DAHDownload,
    },
    {
        name: 'Attestation de fin de formation',
        key: 'document_attestation_fin_formation',
        hide: moment().isBefore(props.cpf.end_at), // cuurent true cpf_end
        // is: DAFFDownload,
    },
    {
        name: 'Questionnaire de satisfaction',
        key: 'document_questionnaire_satisfaction',
        hide: moment().isBefore(props.cpf.end_at), // cuurent true cpf_end
        // is: DqsDownload,
    },
    {
        name: 'Evaluation de sortie de formation',
        key: '1',
        hide: moment().isBefore(props.cpf.end_at), // cuurent true cpf_end
        // is: DEFFDownload,
        bind: {
            studentId: props.cpf.student_id,
        },
    },
    {
        name: 'Questionnaire de suivi de formation',
        key: 'document_suivi_pro',
        hide: moment().isBefore(moment(props.cpf.end_at).add(1, 'month')),
        // is: DSPDownload,
    },
    {
        name: 'Questionnaire de suivi de formation',
        key: 'document_suivi_pro_2',
        hide: moment().isBefore(moment(props.cpf.end_at).add(2, 'month')),

        // is: DSPDownload,
    },
];
</script>
<template>
    <Card block padding :title="cpf.offer?.name">
        <InputField
            id="dateVerification"
            disabled
            :model-value="dateFormat(cpf.date_verif, 'fr') || 'Non renseigné'"
            label="Date verification"
        />
        <ul class="flex flex-col divide-y">
            <li v-for="(doc, idx) in docs" :key="idx" class="flex items-center gap-3 py-1">
                <div class="bg-gray-100 h-12 w-12 flex-center rounded-lg">
                    <NoteIcon class="w-6 h-6" />
                </div>
                <p class="flex-1">{{ doc.name }}</p>
                <!-- <component
                    :is="doc.is"
                    v-if="(cpf[doc.key] || doc.key === '1') && !doc.hide"
                    :item="cpf[doc.key]"
                    title="Telecharger"
                    :cpf="cpf"
                    v-bind="doc.bind || {}"
                /> -->
            </li>
        </ul>
    </Card>
</template>
