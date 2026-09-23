<script setup lang="ts">
import {Head} from '@inertiajs/vue3';
import {Button} from '@shared/components';
import {routes} from "@espace-client/routes.js";
import {PaperCheckIcon} from '@adersolutions/icons';

const props = defineProps(['info'])
</script>

<template>
    <Head title="Test de positionnement en ligne CPF PPF"/>
    <main class="h-screen flex-center flex-col w-full bg-gradient-to-b from-primary to-[#01161d] px-2">
        <div class="fixed z-0 inset-0 opacity-70 pointer-events-none">
            <img src="/assets/bg.svg" class="object-cover w-full h-full"/>
        </div>

        <section
            class="sm:max-w-[700px] max-h-[99%] w-full mx-auto bg-white flex max-md:flex-col p-5 md:p-3 shadow-box rounded-xl gap-4 relative">

            <article class="flex-1 md:px-10 scrollbar overflow-y-auto">
                <div class=" w-full  my-5 ">

                    <span class="flex-center text-sm"><b>Il ne reste plus qu’à télécharger et confirmer le contrat pour avancer ! </b></span>


                </div>
                <div class=" flex flex-col gap-1">
                    <label class="text-primary text-sm font-bold">Les Documents</label>
                    <div class="text-sm font-semibold flex items-center gap-2">
                        <span> Le Document test de positionnement   </span>
                        <a target="_blank" :href="route(routes.cpfForm.testPositionnementPdf,props.info.id)">
                            <PaperCheckIcon
                                :class="['object-contain w-6 text-primary cursor-pointer']"/>
                        </a>
                    </div>
                    <div class="text-sm font-semibold flex  items-center gap-2">
                        <span> Le Document de Contact de formation   </span>

                        <a target="_blank" :href="route(routes.cpfForm.contactFormationPdf,props.info.id)">
                            <PaperCheckIcon :class="['object-contain w-6 text-primary cursor-pointer']"/>
                        </a>
                    </div>

                    <div class="mt-2 justify-end">
                        <Button :href="route(routes.cpfForm.attestationPage,props.info.id)"  variant="primary" full> Suivant</Button>

                    </div>
                </div>
            </article>
        </section>
    </main>
</template>
