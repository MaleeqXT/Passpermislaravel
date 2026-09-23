<script setup lang="ts">
import {
    Alerts,
    Back,
    ButtonGroup,
    Card,
    ColorField,
    EditorField,
    FileLibrary,
    InputField,
    Select,
    SingleImageField,
    Switch,
    TabSwitch,
} from '@shared/components';
import {routes} from '@espace-admin/routes.js';
import {computed, PropType, ref} from 'vue';
import {router, useForm} from '@inertiajs/vue3';
import {LearningModeList, OffreCategoryList, OffreTypeList} from '@common/enums';
import {useFiles} from '@shared/hooks';
import {OfferType} from '@common/types';
import {ButtonType} from '@shared/types';
import { onMounted } from 'vue';


const props = defineProps({
    data: {
        type: Object as PropType<OfferType>,
        default: () => ({}),
    },
    actions: Array,
    isEdit: Boolean,
});

const heading = props.isEdit ? 'Modifier offre' : 'Nouvelle offre';
const medias = useFiles();
const submitting = ref(false);

onMounted(() => {
    if (!props.isEdit || !props.data?.agency_pricing) return;

    const pricing =
        typeof props.data.agency_pricing === 'string'
            ? JSON.parse(props.data.agency_pricing)
            : props.data.agency_pricing;

    pricing.forEach((item: any) => {
        if (item.agency === 'criel') {
            form.criel_price_ht = item.price_ht ?? '';
            form.criel_original_price = item.original_price ?? '';
            form.criel_discounted_price = item.discounted_price ?? '';
            form.criel_second_price = item.second_price ?? '';
            form.criel_balance = item.balance ?? '';
            form.criel_balance_2 = item.balance_2 ?? '';
            form.criel_multi_payment = item.multi_payment ?? '';
            form.criel_total_payment = item.total_payment ?? '';
            form.criel_caracteristiques = item.caracteristiques ?? '';

        }

        if (item.agency === 'toulouse') {
            form.toulouse_price_ht = item.price_ht ?? '';
            form.toulouse_original_price = item.original_price ?? '';
            form.toulouse_discounted_price = item.discounted_price ?? '';
            form.toulouse_second_price = item.second_price ?? '';
            form.toulouse_balance = item.balance ?? '';
            form.toulouse_balance_2 = item.balance_2 ?? '';
            form.toulouse_multi_payment = item.multi_payment ?? '';
            form.toulouse_total_payment = item.total_payment ?? '';
           form.toulouse_caracteristiques = item.caracteristiques ?? '';

        }
    });
});


const form = useForm({
    name: props.data.name || '',
    caracteristiques: props.data.caracteristiques || '',
    description: props.data.description || '',
    color: props.data.color || '#000000',

    // ❌ YE LINE BILKUL NA HO
    // props.data.agency_pricing,

    criel_price_ht: '',
    criel_original_price: '',
    criel_discounted_price: '',
    criel_second_price: '',
    criel_balance: '',
    criel_balance_2: '',
    criel_multi_payment: '',
    criel_total_payment: '',
    toulouse_price_ht: '',
    toulouse_original_price: '',
    toulouse_discounted_price: '',
    toulouse_second_price: '',
    toulouse_balance: '',
    toulouse_balance_2: '',
    toulouse_multi_payment: '',
    toulouse_total_payment: '',
    toulouse_caracteristiques: '',
    is_auto: props.data.is_auto || false,
    is_cpf: props.data.is_cpf || false,
    is_evaluation: props.data.is_evaluation || false,
    type_offre: props.data.type_offre || '',
    type: props.data.type || '',
    status: props.data.status || false,
    is_offer_cart: props.data.is_offer_cart || false,
    media: props.data?.media?.storage_media || null,
    order: props.data.order,
});


const actions = computed<ButtonType[]>(() => [
    {
        label: `${props.isEdit ? 'Modifier' : 'Ajouter'} offre`,
        variant: 'primary',
        full: true,
        type: 'button',
        disabled: submitting.value,
        loading: submitting.value,
        onAction: submit,
    },
    {
        label: 'Abandonner',
        disabled: submitting.value,
        variant: 'secondary',
        full: true,
        onAction: () => form.reset(),
    },
]);

const crielInstallments = computed(() => {
    const total = parseFloat(form.criel_total_payment || 0);
    const multi = parseInt(form.criel_multi_payment || 1);
    if (total > 0 && multi > 1) {
        const each = (total / multi).toFixed(2);
        return Array.from({ length: multi }, (_, i) => ({
            no: i + 1,
            amount: each,
        }));
    }
    return [];
});

const toulouseInstallments = computed(() => {
    const total = parseFloat(form.toulouse_total_payment || 0);
    const multi = parseInt(form.toulouse_multi_payment || 1);
    if (total > 0 && multi > 1) {
        const each = (total / multi).toFixed(2);
        return Array.from({ length: multi }, (_, i) => ({
            no: i + 1,
            amount: each,
        }));
    }
    return [];
});
const submit = async () => {
    if (submitting.value) return;
    submitting.value = true;

    const payload = {
        name: form.name,
        caracteristiques: form.caracteristiques,
        description: form.description,
        color: form.color,
        agency_pricing: [
            {
                agency: 'criel',
                price_ht: form.criel_price_ht,
                caracteristiques: form.caracteristiques,
                original_price: form.criel_original_price,
                discounted_price: form.criel_discounted_price,
                second_price: form.criel_second_price,
                balance: form.criel_balance,
                balance_2: form.criel_balance_2,
                multi_payment: form.criel_multi_payment,
                total_payment: form.criel_total_payment,
                installments: crielInstallments.value,
            },
            {
                agency: 'toulouse',
                price_ht: form.toulouse_price_ht,
                caracteristiques: form.toulouse_caracteristiques,
                original_price: form.toulouse_original_price,
                discounted_price: form.toulouse_discounted_price,
                second_price: form.toulouse_second_price,
                balance: form.toulouse_balance,
                balance_2: form.toulouse_balance_2,
                multi_payment: form.toulouse_multi_payment,
                total_payment: form.toulouse_total_payment,
                installments: toulouseInstallments.value,
            },
        ],
        is_auto: form.is_auto,
        is_cpf: form.is_cpf,
        is_evaluation: form.is_evaluation,
        type_offre: form.type_offre,
        type: form.type,
        status: form.status,
        is_offer_cart: form.is_offer_cart,
        media: form.media?.id || form.media,
        order: form.order,
    };

    try {
        if (props.isEdit) {
            // ✅ UPDATE
            await router.put(
                route(routes.shop.offers.update, props.data.id),
                payload
            );
        } else {
            // ✅ STORE
            await router.post(
                route(routes.shop.offers.store),
                payload
            );
        }
    } finally {
        submitting.value = false;
    }
};


const onDeleteFile = () => {
    if (props.isEdit && props.data.media?.id) {
        medias.delete(props.data.media.id).then(() => {
            form.media = null;
        });
    } else {
        form.media = null;
    }
};
</script>

<template>
    <form class="form-page" @submit.prevent="submit">
        <FileLibrary @submit="form.media = $event"/>
        <Back :title="heading" :back="route(routes.shop.offers.index)"/>

        <article class="left-form">
            <Back :title="heading" :back="route(routes.shop.offers.index)"/>
            <div class="form-content">
                <Alerts/>
                <Card block padding title="Detail offre">
                    <InputField v-model="form.name" :error="form.errors.name" label="Nom du produit"/>
                    <InputField
                        v-model="form.description"
                        :error="form.errors.description"
                        :multiline="3"
                        label="Description du produit "
                    />
                    <EditorField v-model="form.caracteristiques" label="Caractéristiques"
                                 :error="form.errors.caracteristiques"/>
                    <!-- <InputField v-model="form.caracteristiques" :error="form.errors.caracteristiques" :multiline="3" /> -->
                    <div class="grid md:grid-cols-2 gap-5">
                        <Select
                            v-model="form.type"
                            :items="Object.values(OffreCategoryList)"
                            :error="form.errors.type"
                            :keys="['name', 'id']"
                            :show-search="false"
                            :default-value="OffreCategoryList[data?.type]"
                            label="Type"
                            clear
                            placeholder="Choisir type de produit"
                        />
                        <Select
                            v-model="form.type_offre"
                            :error="form.errors.type_offre"
                            :items="Object.values(OffreTypeList)"
                            :keys="['name', 'id']"
                            :default-value="OffreTypeList[data?.type_offre] || ''"
                            :show-search="false"
                            label="Type d'offre"
                            clear
                        />
                    </div>
                    <div class="flex items-center justify-between">
                        <label class="block font-medium text-xs text-gray-600 mb-0.5 mt-1"> Mode
                            d'apprentissage </label>
                        <TabSwitch v-model="form.is_auto" class="text-xs" size="sm" short
                                   :items="Object.values(LearningModeList)"/>
                    </div>
                    <div class="flex items-center justify-between">
                        <label class="block font-medium text-xs text-gray-600 mb-0.5 mt-1">Offre Cpf </label>
                        <Switch v-model="form.is_cpf" class="text-xs bg-gray-200" size="sm"/>
                    </div>
                    <div class="flex items-center justify-between">
                        <label class="block font-medium text-xs text-gray-600 mb-0.5 mt-1">Heure de
                            l'évaluation </label>
                        <Switch v-model="form.is_evaluation" class="text-xs bg-gray-200" size="sm"/>
                    </div>
                </Card>
                <Card block padding title="Prix (Criel)">
                    <div class="grid md:grid-cols-2 gap-3">
                        <InputField v-model="form.criel_price_ht" :error="form.errors.price_ht" label="Prix HT" suffix="€"
                                    type="number"/>
                        <InputField
                            v-model="form.criel_original_price"
                            :error="form.errors.original_price"
                            label="Prix de base (TTC)"
                            suffix="€"
                            type="number"
                        />
                        <InputField
                            v-model="form.criel_discounted_price"
                            :error="form.errors.discounted_price"
                            label="Le prix après réduction"
                            type="number"
                        />
                        <InputField v-model="form.criel_balance" :error="form.errors.balance" label="Balance" suffix="H"
                                    type="number"/>

                                        <InputField v-model="form.criel_balance_2" :error="form.errors.balance_2" label="Balance (2)" suffix="H" type="number"/>

                        <InputField
                            v-model="form.criel_multi_payment"
                            :error="form.errors.multi_payment"
                            label="Tranche de paiement"
                            type="number"
                        />

                        <InputField
                            v-model="form.criel_total_payment"
                            :error="form.errors.total_payment"
                            label="Paiement Total"
                            suffix="€"
                            type="number"
                        />

                         <!-- <InputField
                            v-model="form.multi_payment"
                            :error="form.errors.multi_payment"
                            label="Agency Name"
                            type="text"
                        /> -->

                          <InputField v-model="form.criel_second_price" :error="form.errors.second_price" label="Deuxième prix" suffix="€" type="number"/>

                              <Select
                                  :items="[
                                      { name: 'Criel', id: 'criel' }
                                  ]"
                                  :keys="['name', 'id']"
                                  :show-search="false"
                                  :default-value="'criel'"
                                  label="Nom de l'agence"
                              />
                    </div>

                     <EditorField v-model="form.creil_caracteristiques" label="Caractéristiques (CREIL)"
                                 :error="form.errors.creil_caracteristiques"/>

                    <!-- Installments Preview for Criel -->
                    <div v-if="crielInstallments.length > 0" class="mt-6 p-4 bg-blue-50 rounded-lg">
                        <h3 class="font-semibold text-sm mb-3 text-gray-700">Aperçu des paiements par tranche (Criel)</h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                            <div v-for="installment in crielInstallments" :key="installment.no" class="bg-white p-3 rounded border border-blue-200">
                                <p class="text-xs text-gray-500">Tranche {{ installment.no }}</p>
                                <p class="font-semibold text-sm text-blue-600">{{ installment.amount }}€</p>
                            </div>
                        </div>
                    </div>
                </Card>
                  <Card block padding title="Prix (Toulouse)">
                    <div class="grid md:grid-cols-2 gap-3">
                        <InputField v-model="form.toulouse_price_ht" :error="form.errors.price_ht" label="Prix HT" suffix="€"
                                    type="number"/>
                        <InputField
                            v-model="form.toulouse_original_price"
                            :error="form.errors.original_price"
                            label="Prix de base (TTC)"
                            suffix="€"
                            type="number"
                        />
                        <InputField
                            v-model="form.toulouse_discounted_price"
                            :error="form.errors.discounted_price"
                            label="Le prix après réduction"
                            type="number"
                        />
                        <InputField v-model="form.toulouse_balance" :error="form.errors.balance" label="Balance" suffix="H"
                                    type="number"/>

                                        <InputField v-model="form.toulouse_balance_2" :error="form.errors.balance_2" label="Balance (2)" suffix="H" type="number"/>

                        <InputField
                            v-model="form.toulouse_multi_payment"
                            :error="form.errors.multi_payment"
                            label="Tranche de paiement"
                            type="number"
                        />

                        <InputField
                            v-model="form.toulouse_total_payment"
                            :error="form.errors.total_payment"
                            label="Paiement Total"
                            suffix="€"
                            type="number"
                        />

                         <!-- <InputField
                            v-model="form.multi_payment"
                            :error="form.errors.multi_payment"
                            label="Agency Name"
                            type="text"
                        /> -->

                          <InputField v-model="form.toulouse_second_price" :error="form.errors.second_price" label="Deuxième prix" suffix="€" type="number"/>

                              <Select
                                  :items="[
                                      { name: 'Toulouse', id: 'toulouse' }
                                  ]"
                                  :keys="['name', 'id']"
                                  :show-search="false"
                                  :default-value="'toulouse'"
                                  label="Nom de l'agence"
                              />
                    </div>
                    <EditorField v-model="form.toulouse_caracteristiques" label="Caractéristiques (Toulouse)"
                                 :error="form.errors.toulouse_caracteristiques"/>

                    <!-- Installments Preview for Toulouse -->
                    <div v-if="toulouseInstallments.length > 0" class="mt-6 p-4 bg-green-50 rounded-lg">
                        <h3 class="font-semibold text-sm mb-3 text-gray-700">Aperçu des paiements par tranche (Toulouse)</h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                            <div v-for="installment in toulouseInstallments" :key="installment.no" class="bg-white p-3 rounded border border-green-200">
                                <p class="text-xs text-gray-500">Tranche {{ installment.no }}</p>
                                <p class="font-semibold text-sm text-green-600">{{ installment.amount }}€</p>
                            </div>
                        </div>
                    </div>
                </Card>
            </div>
            
            <ButtonGroup vertical class="form-actions" :actions="actions"/>

        </article>
        <article class="right-form">
            <div>
                <ColorField v-model="form.color" :error="form.errors.color" label="Couleur"/>
                <Switch v-model="form.is_offer_cart" :error="form.errors.is_offer_cart" label="C'est offre de Pannier"/>
                <Switch v-model="form.status" :disabled="form.is_cpf" :error="form.errors.status" label="Activé "/>
                <SingleImageField :error="form.errors.media" :src="form.media?.path || form.media || undefined"
                                  @delete="onDeleteFile"/>
                <InputField
                    v-model="form.order"
                    :error="form.errors.order"
                    label="Ordre d'affichage"
                    min="0"
                    type="number"
                />
            </div>
            <ButtonGroup vertical class="form-actions" :actions="actions"/>
        </article>
    </form>
</template>
