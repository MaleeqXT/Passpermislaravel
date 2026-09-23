<script setup lang="ts">
import { Button } from '@shared/components';
import { ref } from 'vue';
import { downloadPdf } from '@common/utils';
import { PageDownIcon } from '@adersolutions/icons';
import { useApp } from '@shared/stores';
import moment from 'moment-timezone';
import { useQuery } from '@shared/hooks';
import { routes } from '@espace-admin/routes';
import Skill from '@espace-admin/features/roles/students/views/partials/Competency.vue';
defineEmits(['close']);
const props = defineProps({
    cpf: {
        type: Object,
        default: () => ({}),
    },
    title: String,
    studentId: String,
});
const doc = ref<null | HTMLElement>(null);
const { user } = useApp();
const skillQuery = useQuery(
    {
        url: route(routes.api.competences.index, props.studentId),
    },
    true
);

const download = () => {
    downloadPdf(doc.value, {
        name: 'Evaluation fin de formation',
        unit: 'px',
        x: 0,
        y: 0,
        width: 450,
        windowWidth: 900,
    });
};
</script>

<template>
    <div class="contents">
        <Button class="btn-pdf" link :loading="skillQuery.fetching" :icon="PageDownIcon" info @click="download">
            {{ title || 'Télécharger mon document' }}
        </Button>
        <div class="hidden">
            <div ref="doc" class="prose prose-sm max-w-full font-pdf">
                <div class="page">
                    <img src="/assets/logo.svg" alt="passpermisfacile" class="h-12 mx-auto my-3" />
                    <h2 class="text-center mt-0 mb-10">Evaluation fin de formation</h2>
                    <p>{{ user.name }}</p>
                    <p>
                        Vous avez choisi de mobiliser vos droits CPF pour obtenir un permis de conduire de la typologie mentionnée à
                        l’article R. 221-4 du code de la route : Catégorie B.
                    </p>
                    <p>
                        Ce document est votre évaluation de fin de formation. Cette dernière reprend l’ensemble des compétences que vous
                        avez travaillé avec votre/vos Moniteur(s).
                    </p>
                    <p>Vous y trouverez l'appréciation du Moniteur et votre niveau d’acquisition de la compétence.</p>
                </div>

                <div :style="{ all: 'unset' }">
                    <div v-for="item in skillQuery.data" :key="item.id" class="font-pdf page">
                        <h3 class="text-slate-700 text-center">{{ item.name }}</h3>
                        <Skill :group="item" />
                    </div>
                </div>
                <div class="page">
                    <p>Nous te remercions d'avoir choisi nous !</p>
                    <div class="max-w-60 flex-center flex-col ml-auto">
                        <p class="text-center ml-auto">
                            EasyMonitor le
                            {{ moment().format('DD/MM/YYYY') }}
                        </p>
                        <img src="/assets/images/common/contract.png" class="w-ull mt-1 block" alt="contract" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<style scoped lang="scss">
.page {
    @apply bg-white py-5 px-14 aspect-[1/1.403] rounded-xl relative font-pdf max-md:text-xs;
}
</style>
