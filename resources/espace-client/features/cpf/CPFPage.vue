<script setup lang="ts">
import { ClientButton, PageContainer } from '@espace-client/components';
import OfferCard from './partials/OfferCard.vue';
import { DataListType } from '@shared/types';
import { OfferType } from '@common/types';
import { InputField, Select, TabSwitch } from '@shared/components';
import { useQuery, useRoute } from '@shared/hooks';
import { OffreCategoryEnum } from '@common/enums';
import { router, useForm } from '@inertiajs/vue3';
import { routes as AdminRoutes } from '@espace-admin/routes';
import { routes } from '@espace-client/routes.js';

type PropsType = {
    offers: DataListType<OfferType>;
};

defineProps<PropsType>();
const areaQuery = useQuery({
    url: route(AdminRoutes.api.locations.area.index),
    mounted: true,
});
const offersQuery = useQuery({
    url: route(AdminRoutes.api.offers.index),
    mounted: true,
});
const params = useRoute<{ is_auto: number; type: number }>();
const form = useForm({
    name: '',
    email: '',
    phone: '',
    secteur: '',
    forfait: '',
    rappel: '',
    disponible: '',
});
const onSubmit = () => {
    router.get('/forms-cpf', form.data());
    // form.post(route('cpf.store'), {
    //     onSuccess: () => {
    //         form.reset();
    //     },
    // });
};
</script>
<style lang="scss" scoped>
.stepps {
    @apply bg-sky-300 relative text-white w-8 h-8 rounded-full flex justify-center items-center  md:-ml-4;
}
</style>

<template>
    <PageContainer>
        <section class="bg-gray-200">
            <div class="max-w-7xl grid md:grid-cols-12 gap-5 md:gap-14 mx-auto px-3 py-7 sm:px-6 sm:py-16 lg:px-8">
                <article class="md:col-span-7 grid text-[13px] h-fit">
                    <!-- <h2>Fonctionnement CPF</h2> -->

                    <p class="text-sm md:text-md font-bold mb-4">
                        Pour faciliter l’utilisation de votre Compte Personnel de Formation (CPF) <br />
                        pour financer votre permis de conduire au sein de notre auto-école, <br />
                        voici un guide simple en quatre étapes :
                    </p>
                    <ul class="md:flex md:flex-col gap-3 h-fit slider-mobile">
                        <li class="relative slider-item max-md:rounded-lg max-md:bg-white max-md:py-2 max-md:px-3">
                            <span class="hidden md:flex line-v absolute left-0 z-1 top-1 -bottom-10"></span>

                            <h3 class="font-bold text-sm flex gap-3 items-center relative z-1 w-fit pr-2 max-md:mb-2">
                                <span class="stepps">1</span>
                                Vérifiez votre éligibilité
                            </h3>
                            <ul class="list-disc list-outside pl-3 md:pl-10">
                                <li>
                                    <strong>Conditions requises :</strong> Assurez-vous que l’obtention du permis de conduire est nécessaire
                                    pour votre projet professionnel.
                                </li>
                                <li>
                                    <strong>Solde CPF :</strong> Connectez-vous à votre compte sur
                                    <a href="https://www.moncompteformation.gouv.fr" target="_blank">moncompteformation.gouv.fr</a> pour
                                    consulter vos droits disponibles.
                                </li>
                            </ul>
                        </li>
                        <li class="relative slider-item max-md:rounded-lg max-md:bg-white max-md:py-2 max-md:px-3">
                            <span class="hidden md:flex line-v absolute left-0 z-1 top-1 -bottom-10"></span>

                            <h3 class="font-bold text-sm flex gap-3 items-center relative z-1 w-fit pr-2 max-md:mb-2">
                                <span class="stepps">2</span>
                                Inscrivez-vous à notre formation
                            </h3>
                            <ul class="list-disc list-outside pl-3 md:pl-10">
                                <li>
                                    <strong>Recherche de formation :</strong> Sur le site
                                    <a href="https://www.moncompteformation.gouv.fr" target="_blank">moncompteformation.gouv.fr</a>,
                                    recherchez notre auto-école en entrant notre nom ou notre numéro d’agrément.
                                </li>
                                <li>
                                    <strong>Sélection de l’offre :</strong> Choisissez la formation au permis de conduire correspondant à
                                    vos besoins (code, conduite ou les deux).
                                </li>
                            </ul>
                        </li>
                        <li class="relative slider-item max-md:rounded-lg max-md:bg-white max-md:py-2 max-md:px-3">
                            <span class="hidden md:flex line-v absolute left-0 z-1 top-1 -bottom-10"></span>

                            <h3 class="font-bold text-sm flex gap-3 items-center relative z-1 w-fit pr-2 max-md:mb-2">
                                <span class="stepps">3</span>
                                Complétez votre dossier
                            </h3>
                            <ul class="list-disc list-outside pl-3 md:pl-10">
                                <li>
                                    <strong>Informations personnelles :</strong> Renseignez les détails demandés, tels que votre identité et
                                    vos coordonnées.
                                </li>
                                <li>
                                    <strong>Attestation sur l’honneur :</strong> Certifiez que le permis est indispensable pour votre
                                    carrière et que vous n’êtes pas sous le coup d’une suspension de permis.
                                </li>
                            </ul>
                        </li>
                        <li class="relative slider-item max-md:rounded-lg max-md:bg-white max-md:py-2 max-md:px-3">
                            <h3 class="font-bold text-sm flex gap-3 items-center relative z-1 w-fit pr-2 max-md:mb-2">
                                <span class="stepps">4</span>
                                Validez et commencez votre formation
                            </h3>
                            <ul class="list-disc list-outside pl-3 md:pl-10">
                                <li>
                                    <strong>Validation de la demande :</strong> Une fois votre dossier soumis, nous l’examinerons et vous
                                    enverrons une confirmation.
                                </li>
                                <li>
                                    <strong>Participation forfaitaire :</strong> Depuis le 2 mai 2024, une contribution de 100 € est requise
                                    pour toute formation financée par le CPF, sauf pour les demandeurs d’emploi.
                                </li>
                                <li>
                                    <strong>Démarrage de la formation :</strong> Après validation et paiement, vous pourrez planifier vos
                                    sessions de formation selon vos disponibilités.
                                </li>
                            </ul>
                        </li>
                    </ul>
                </article>
                <form class="md:col-span-5 bg-white grid gap-5 p-4 box md:sticky top-0 max-md:-mb-80" @submit.prevent="onSubmit">
                    <h2 class="text-xl font-bold">Formulaire à remplir</h2>
                    <InputField id="name01" v-model="form.name" :error="form.errors.name" label="Nom et Prénom" class="flex-1" required />
                    <!-- <InputField
                            id="lastName"
                            v-model="form.last_name"
                            :error="form.errors.last_name"
                            label="Nom"
                            class="flex-1"
                            required
                        /> -->
                    <InputField id="email" v-model="form.email" :error="form.errors.email" label="Email" required type="email" />
                    <InputField
                        id="tel"
                        v-model="form.phone"
                        :error="form.errors.phone"
                        label="Telephone"
                        type="tel"
                        mask="## ## ## ## ##"
                        :length="10"
                        required
                    />

                    <Select
                        v-model="form.secteur"
                        label="Secteur de conduite"
                        :show-search="false"
                        :query="areaQuery"
                        :error="form.errors.secteur"
                        class="w-full"
                        required
                    />
<!--                    <Select-->
<!--                        v-model="form.forfait"-->
<!--                        label="Forfait CPF désiré "-->
<!--                        :show-search="false"-->
<!--                        :query="offersQuery"-->
<!--                        :error="form.errors.forfait"-->
<!--                        class="w-full"-->
<!--                    />-->
                    <Select
                        v-model="form.rappel"
                        label="Préférence de rappel"
                        :show-search="false"
                        :items="[
                            { id: 'Matinée', name: 'Matinée' },
                            { id: 'Après-midi', name: 'Après-midi' },
                        ]"
                        :error="form.errors.rappel"
                        class="w-full"
                    />
                    <InputField
                        id="disponible"
                        v-model="form.disponible"
                        :error="form.errors.disponible"
                        label="Quel(s) jour(s) êtes vous disponible pour vous rappeler?
"
                    />
                    <ClientButton class="w-full" type="submit"> ENVOYER VOTRE DEMANDE </ClientButton>
                    <p>
                        Pour toute question ou assistance supplémentaire, n’hésitez pas à nous contacter directement au
                        <strong>09.70.70.16.16</strong>.
                    </p>
                </form>
            </div>
        </section>

        <div class="flex flex-col items-center justify-center max-md:px-3 max-md:pt-60">
            <img src="/assets/clients/cpf3.webp" alt="" class="h-16 w-auto mx-auto mt-20" />
            <div class="flex flex-col items-center justify-center">
                <TabSwitch
                    class="mt-5 shadow"
                    v-model="params.is_auto"
                    size="xl"
                    :items="[
                        { name: 'Formation en boîte manuelle', id: 0, class: 'danger' },
                        { name: 'Formation en boîte Automatique', id: 1, class: 'info' },
                    ]"
                    :active="params.is_auto ? 0 : 1"
                    @change="params.set({ is_auto: $event })"
                />
                <TabSwitch
                    class="mt-3 bg-white shadow"
                    v-model="params.type"
                    size="xl"
                    light
                    :items="[
                        { name: 'AVEC LE CODE  (intensif : 10h en présentiel)', id: 0, class: 'warning' },
                        { name: 'SANS LE CODE', id: OffreCategoryEnum.CONDUITE, class: 'success' },
                    ]"
                    :active="params.type  ? 1 : 2"
                    @change="params.set({ type: $event })"
                />
            </div>
        </div>
        <div class="mx-auto max-w-2xl px-3 sm:px-6 lg:max-w-5xl lg:px-8">
            <div class="flex flex-col items-center justify-center pt-3">
                <ClientButton
                    :href="route(routes.formationPermisB.index)"
                    variant="light"
                    class="w-full max-w-2xl !bg-violet-700 !text-white !border-transparent hover:!bg-violet-800"
                >
                    ℹ️ Informations Formation Permis B (CPF)
                </ClientButton>
                <p class="mt-2 max-w-2xl text-center text-xs text-slate-600">
                    Détails de la formation Permis B (infos légales et pédagogiques)
                </p>


            </div>
        </div>
        <div class="mx-auto max-w-2xl px-3 py-4 sm:px-6 sm:py-5 lg:max-w-5xl lg:px-8">
            <ul v-if="params.loading" class="mt-8 grid grid-cols-1 gap-y-12 sm:grid-cols-2 sm:gap-x-6 lg:grid-cols-3 xl:gap-x-8 pb-20">
                <li v-for="i in 3" :key="i" class="bg-white box p-5 overflow-hidden flex flex-col">
                    <span class="h-40 w-40 mx-auto bg-gray-300 animate-pulse rounded-lg"></span>
                    <span class="h-36 w-36 mx-auto bg-gray-300 animate-pulse rounded-full mt-5"></span>
                    <span class="h-6 rounded-lg bg-gray-300 animate-pulse mt-10"></span>
                    <span class="h-6 rounded-lg bg-gray-300 animate-pulse mt-4"></span>
                    <span class="h-6 rounded-lg bg-gray-300 animate-pulse mt-4"></span>
                    <span class="h-6 rounded-lg bg-gray-300 animate-pulse mt-4"></span>
                    <span class="h-10 rounded-lg bg-gray-300 animate-pulse mt-10"></span>
                </li>
            </ul>
            <ul v-else class="mt-8 grid grid-cols-1 gap-y-12 sm:grid-cols-2 sm:gap-x-6 lg:grid-cols-3 xl:gap-x-8 pb-20">
                <OfferCard v-for="offer in offers.data" :key="offer.id" :offer="offer" />
            </ul>
        </div>
    </PageContainer>
</template>
