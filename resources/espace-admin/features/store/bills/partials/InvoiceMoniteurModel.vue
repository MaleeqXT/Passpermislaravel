<script setup lang="ts">
import { dateFormat, moneyFormat } from '@shared/utils';
import moment from 'moment-timezone';

defineProps({
    invoice: {
        type: Object,
        default: () => ({}),
    },
});

const headings = [
    { name: 'Description' },
    { name: 'Prix unitaire TTC', className: 'text-center' },
    { name: 'Quantité', className: 'text-center' },
    { name: 'Total HT', className: 'text-center' },
];
</script>

<template>
    <div class="bg-white rounded-xl px-6 py-28 font-pdf">
        <div class="flex justify-between items-center border-b-4 border-primary pb-5 mb-10">
            <h1 class="text-2xl font-bold mb-4">
                Facture n° <b>{{ invoice?.num_facture }}</b>
            </h1>

            <img src="/assets/logo.svg " class="w-20" alt="Auto école" />
        </div>
        <ul class="flex flex-col gap-10">
            <li class="max-w-xs">
                <b class="mb-3 block">De</b>
                <p>
                    {{ invoice.monitor?.user?.name }}, {{ invoice.monitor?.document_professionnel?.forme_juridique }},
                    {{ invoice.monitor?.user?.adresse }}, {{ invoice.monitor?.user?.ville }}, France
                </p>
            </li>
            <li class="flex flex-col">
                <b class="mb-3">Vers</b>
                <i> Société Lorem, ipsum. </i>
                <i> Lorem ipsum dolor sit amet consectetur adipisicing elit. Dolorem, obcaecati. </i>
                <i> SIRET : Lorem, ipsum dolor. </i>
                <i> Téléphone : Lorem, ipsum. </i>
            </li>
        </ul>
        <ul class="grid grid-cols-2 items-start gap-2 px-4 mt-20 mb-5">
            <li class="flex items-center">
                <b class="flex-1">Du : {{ dateFormat(invoice?.from, 'fr') }} </b>
                <b class="flex-1">Au : {{ dateFormat(invoice?.to, 'fr') }}</b>
            </li>
            <li class="flex flex-col items-center">
                <b>{{ moment().format('DD/MM/yyyy') }}</b>
                <b class="text-xl text-primary">{{ moneyFormat(invoice?.montant) }}</b>
            </li>
        </ul>
        <div class="flex-1 w-full py-10">
            <table class="w-full">
                <thead class="border-y text-left">
                    <th
                        v-for="item in headings"
                        :key="item.name"
                        :class="['min-w-0 py-2 px-3 text-left text-lg font-semibold', item.className]"
                    >
                        {{ item.name }}
                    </th>
                </thead>
                <tbody class="divide-y">
                    <tr>
                        <td class="cell text-lg">
                            <p class="font-semibold">Prestation de service</p>
                        </td>
                        <td class="cell text-lg text-center">
                            {{ moneyFormat(invoice?.details.prix_heure, ' €') }}
                        </td>
                        <td class="cell text-lg text-center">{{ invoice?.details.num_heures_nf }}h</td>

                        <td class="cell text-lg text-right">
                            {{ moneyFormat(invoice?.details.total) }}
                        </td>
                    </tr>
                    <tr>
                        <td class="cell text-lg">
                            <p class="font-semibold">Mise à disposition du véhicule</p>
                        </td>

                        <td class="cell text-lg text-center">
                            {{ moneyFormat(invoice?.details.prix_heure, ' €') }}
                        </td>
                        <td class="cell text-lg text-center">{{ invoice?.details.num_heures_nf }}h</td>
                        <td class="cell text-lg text-right">
                            {{ moneyFormat(invoice?.details.prix_heure, ' €') }}
                        </td>
                    </tr>
                    <tr v-for="i in 3" :key="i" class="h-10">
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                </tbody>
                <tfoot class="border-t">
                    <!-- subtotal -->
                    <tr class="h-10">
                        <td colspan="3" class="cell text-right"></td>
                        <td class="cell text-right text-primary"></td>
                    </tr>
                    <tr>
                        <td colspan="3" class="px-3 py-2 text-right text-2xl font-bold">Total TTC</td>
                        <td class="px-3 py-2 text-right text-primary text-2xl font-bold">
                            {{ moneyFormat(invoice?.montant) }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <ul class="flex gap-10 p-4 my-10 text-sm">
            <li class="leading-loose">
                <b class="mb-3 block">Siège social</b>
                <p>10 rue albert thomas, Creil 60100 France</p>
            </li>
            <li class="leading-loose">
                <b class="mb-3 block">Coordonnées</b>
                <p>SIRET : 97830135600017</p>
                <p>E-mail : contact.passpermisfacile@gmail.com</p>
            </li>
        </ul>
        <div class="border-b-[16px] border-primary"></div>
    </div>
</template>
