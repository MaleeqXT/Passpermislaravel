<script setup>
import { ref } from 'vue';
import { RadioGroup, RadioGroupOption } from '@headlessui/vue';
import { CheckCircleIcon, TrashIcon } from '@heroicons/vue/20/solid';
import { PageContainer } from '@espace-client/components';
import { DialogAuth, TabSwitch, Card } from '@shared/components';
import { ConfirmedPaymentModal, PaymentCardPaypal, PaymentCardStripe } from '@common/components';
import { reactive } from 'vue';
import { ArrowLeftIcon, ArrowRightIcon, ChevronLeftIcon, LockFilledIcon } from '@adersolutions/icons';
import { useApp, useCart } from '@shared/stores';
import { getFilePath, moneyFormat } from '@shared/utils';
import { Link } from '@inertiajs/vue3';
import SummaryOrder from './partials/SummaryOrder.vue';
import { computed } from 'vue';
import { routes } from '@espace-client/routes';
import { useRoute } from '@shared/hooks';

const cart = useCart();
const { user } = useApp();
const params = useRoute();
const tranchePayment = computed(() => [
    { id: false, title: 'Standard', desc: "Paiement unique - Réglez l'intégralité du montant en une seule transaction" },
    {
        id: true,
        title: 'Paiement par tranche',
        desc: 'Le paiement par cette methode sera bientôt disponible.', //'Option de paiement échelonné - Divisez votre paiement en plusieurs tranches',
        disabled: true, //!cart.getTranche(),
    },
]);

const state = reactive({
    cc: true,
});
const onBack = () => {
    if (params.redirect_to) {
        location.href = params.redirect_to;
    }
    history.back();
};
</script>
<template>
    <PageContainer title="Checkout" class="bg-white">
        <!-- <DialogAuth /> -->
        <ConfirmedPaymentModal />

        <div class="absolute lg:w-1/3 inset-y-0 right-0 bg-gray-100 z-0 border-l border-gray-200 text-dark2"></div>
        <section class="flex flex-col lg:flex-row relative z-1 h-full">
            <div class="lg:w-2/3">
                <article class="mx-auto max-w-2xl px-4 pt-6 md:pb-24 md:pt-16 sm:px-6 lg:max-w-2xl lg:px-8 w-full max-lg:order-3">
                    <div>
                        <div class="flex items-center gap-3 mb-6">
                            <button @click="onBack" class="bg-slate-100 flex-center w-10 h-10 rounded-lg">
                                <ArrowLeftIcon class="w-5 h-5 text-gray-700" />
                            </button>
                            <h2 class="text-xl md:text-4xl">Finalisez Votre Commande</h2>
                        </div>
                        <p class="text-md font-semibold">Eleve information</p>

                        <div class="relative flex items-center space-x-3 rounded-lg bg-slate-100 p-3 box mb-6">
                            <div class="shrink-0">
                                <img class="size-10 rounded-lg bg-white" :src="getFilePath(user)" :alt="user.name" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <a href="/admin" class="focus:outline-none">
                                    <span class="absolute inset-0" aria-hidden="true" />
                                    <p class="text-md font-medium text-gray-900">{{ user.name }}</p>
                                    <p class="truncate text-sm text-gray-500">{{ user.email }} | {{ user.phone }}</p>
                                </a>
                            </div>
                            <ArrowRightIcon class="w-5 h-5 text-gray-500" />
                        </div>
                    </div>
                    <SummaryOrder class="lg:hidden" />

                    <div class="relative my-4 lg:my-8">
                        <div class="absolute inset-0 flex items-center" aria-hidden="true">
                            <div class="w-full border-t border-gray-200" />
                        </div>
                        <div class="relative flex justify-center">
                            <span class="bg-white px-4 text-sm font-medium text-gray-500"> Choisissez votre mode de paiement </span>
                        </div>
                    </div>
                    <!-- <TabSwitch
                        class="mb-6 bg-rainbow shadow-sm"
                        full
                        v-model="state.cc"
                        size="lg"
                        :items="[
                            {
                                name: 'Carte bancaire',
                                id: true,
                                class: 'secondary',
                            },
                            {
                                name: 'Paypal',
                                id: false,
                                class: 'secondary',
                            },
                        ]"
                    /> -->
                    <div>
                        <div class="mb-2">
                            <h2 class="text-md font-semibold">Type Paiement</h2>

                            <fieldset aria-label="Type Paiement" class="mt-2 lg:mt-3">
                                <RadioGroup
                                    v-model="cart.state.isSpliteActive"
                                    class="grid grid-cols-1 gap-y-1 lg:gap-y-6 sm:grid-cols-2 sm:gap-x-4"
                                >
                                    <RadioGroupOption
                                        as="template"
                                        v-for="tranchOption in tranchePayment"
                                        :key="tranchOption.id"
                                        :value="tranchOption.id"
                                        :aria-label="tranchOption.title"
                                        :aria-description="`${tranchOption.desc} for ${tranchOption.tranche}`"
                                        v-slot="{ active, checked }"
                                        :disabled="tranchOption.disabled"
                                    >
                                        <div
                                            :class="[
                                                checked ? 'border-transparent' : 'border-gray-300',
                                                active ? 'ring-2 ring-primary' : '',
                                                tranchOption.disabled && 'opacity-30',
                                                'relative flex cursor-pointer rounded-lg border bg-white px-2 py-1 lg:p-3 shadow-sm focus:outline-none',
                                            ]"
                                        >
                                            <p class="flex flex-col flex-1">
                                                <span class="block text-sm font-medium text-gray-900">{{ tranchOption.title }}</span>
                                                <span class="mt-1 flex items-center text-2xs text-gray-500">{{ tranchOption.desc }}</span>
                                            </p>
                                            <CheckCircleIcon v-if="checked" class="size-5 text-primary" aria-hidden="true" />
                                            <span
                                                :class="[
                                                    active ? 'border' : 'border-2',
                                                    checked ? 'border-primary' : 'border-transparent',
                                                    'pointer-events-none absolute -inset-px rounded-lg',
                                                ]"
                                                aria-hidden="true"
                                            />
                                        </div>
                                    </RadioGroupOption>
                                </RadioGroup>
                            </fieldset>
                        </div>
                        <PaymentCardStripe v-if="state.cc">
                            <p class="text-sm text-gray-500 mb-2">
                                Votre commande est presque terminée ! En cliquant sur « Confirmer et payer », vous acceptez nos Conditions

                                <a :href="route(routes.legal.sales)" target="_blank" class="text-blue-500 underline">
                                    Générales de Vente
                                </a>
                                et notre
                                <a :href="route(routes.legal.privacy)" target="_blank" class="text-blue-500 underline">
                                    Politique de Confidentialité
                                </a>
                            </p>
                        </PaymentCardStripe>
                        <PaymentCardPaypal v-else>
                            <p class="text-sm text-gray-500 mb-2">
                                Votre commande est presque terminée ! En cliquant sur « Connecter et payer avec Paypal », vous acceptez nos
                                Conditions

                                <a :href="route(routes.legal.sales)" target="_blank" class="text-blue-500 underline">
                                    Générales de Vente
                                </a>
                                et notre
                                <a :href="route(routes.legal.privacy)" target="_blank" class="text-blue-500 underline">
                                    Politique de Confidentialité
                                </a>
                            </p>
                        </PaymentCardPaypal>
                        <p class="flex-center bg-rainbow py-2 text-sm">
                            <LockFilledIcon class="w-5 h-5 text-gray-500" />
                            Paiement 100% sécurisé – Cryptage SSL
                        </p>

                        <div class="relative my-4 lg:my-8">
                            <div class="absolute inset-0 flex items-center" aria-hidden="true">
                                <div class="w-full border-t border-gray-200" />
                            </div>
                            <div class="relative flex justify-center">
                                <span class="bg-white px-4 text-sm font-medium text-gray-500"> Lire les conditions de vente </span>
                            </div>
                        </div>

                        <div class="text-xs text-gray-500 mb-20">
                            <p class="mb-2">
                                <span class="font-semibold">Carte de crédit</span> – Nous acceptons les cartes Visa, Mastercard et American
                                Express.
                            </p>
                            <p class="mb-2">
                                <span class="font-semibold">Paypal</span> – Vous pouvez également payer avec votre compte Paypal.
                            </p>
                            <p class="mb-2">
                                <span class="font-semibold">Sécurité</span> – Nous utilisons le cryptage SSL pour protéger vos informations
                                personnelles.
                            </p>
                            <p class="mb-2">
                                <span class="font-semibold">Politique de confidentialité</span> – Vos informations personnelles ne seront
                                pas partagées avec des tiers sans votre consentement.
                            </p>
                            <p class="mb-2">
                                <span class="font-semibold">Conditions générales</span> – En passant une commande, vous acceptez nos
                                conditions générales de vente.
                            </p>

                            <p class="mb-2">
                                <span class="font-semibold">Assistance</span> – Si vous avez des questions, n'hésitez pas à nous contacter à
                                l'adresse <a href="mailto:contact@example.com">contact@example.com</a>.
                            </p>
                            <p class="mb-2">
                                <span class="font-semibold">Politique de remboursement</span> – Si vous n'êtes pas satisfait de votre achat,
                                nous vous rembourserons intégralement dans les 30 jours suivant la date d'achat.
                            </p>
                            <p class="mb-2">
                                <span class="font-semibold">Assistance clientèle</span> – Notre équipe d'assistance est disponible 24/7 pour
                                répondre à toutes vos questions.
                            </p>
                        </div>
                    </div>
                </article>
            </div>
            <!-- Order summary -->
            <SummaryOrder class="max-lg:hidden" />
        </section>
    </PageContainer>
</template>
