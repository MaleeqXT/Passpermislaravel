<script setup lang="ts">
import { reactive } from 'vue';
import { DateField, Select, InputField } from '@shared/components';
import { useForm } from '@inertiajs/vue3';
import { routes } from '@espace-client/routes';
import { checkPassword } from '@shared/utils';
import { ClientButton } from '@espace-client/components';
import { ref, watch } from 'vue'



const form = useForm({
    first_name: '',
    last_name: '',
    email: '',
    adresse: '',
    phone: '',
    media: '',
    sexe: '',
    date_naissance: '',
    postal: '',
    postal_code_1: '',
    ville: '',
    neph: '',
    date_code: '',
     has_neph: '', // temporary field for Oui/Non selection (only for UI)
    how_know: '',
    password: '',
    boite_type:'',
    password_confirmation: '',
    gearbox_type: '', // 👈 add this line (BM or BA)
});





const showNephField = ref(false)

const postalCodes = {
    Creil: '60100',
    Toulouse: '31300',
};

watch(() => form.has_neph, (newVal) => {
  if (newVal === 'Oui') {
    showNephField.value = true
  } else {
    showNephField.value = false
    form.neph = '' // clear NEPH number when Non
  }
})

watch(() => form.ville, (newVille) => {
  form.postal = postalCodes[newVille] || ''
})

const onSubmit = () => {
    form.post(route(routes.register.student.store), {
        preserveScroll: true,
        onSuccess: () => {
            // registration succeeded – send user to login page instead of nonexistent student route
            location.href = route('login');
        },
        onError: () => {
            form.password = '';
            form.password_confirmation = '';
        },
    });
};
</script>

<template>
    <form class="space-y-4" @submit.prevent="onSubmit">
        <div class="bg-white shadow-box rounded-xl p-5 space-y-3">
            <div class="grid md:grid-cols-2 gap-3">
                <InputField v-model="form.first_name" required name="first-name" :error="form.errors.first_name" label="Prénom" />
                <InputField v-model="form.last_name" required name="last-name" :error="form.errors.last_name" label="Nom" />
            </div>

            <div class="grid md:grid-cols-2 gap-3">
                <InputField v-model="form.email" required :error="form.errors.email" name="email" label="Email" />
                <InputField v-model="form.phone" :error="form.errors.phone" required name="phone" label="Numéro de téléphone" type="tel" />
            </div>
        </div>

        <div class="bg-white shadow-box rounded-xl p-5 space-y-3">

            <div class="grid md:grid gap-3">
    <!-- Type de boîte -->
    <Select
        v-model="form.boite_type"
        label="Pour votre formation, quel type de véhicule souhaitez-vous utiliser ?"
        :show-search="false"
        :items="[
            { id: '0', name: 'Boîte Manuelle (BM)' },
            { id: '1', name: 'Boîte Automatique (BA)' },
        ]"
        :error="form.errors.boite_type"
        placeholder="Choisir le type de boîte"
    />

    <!-- Centre de formation -->
    <Select
        v-model="form.ville"
        label="Centre de formation"
        :show-search="false"
        :items="[
            { id: 'Creil', name: 'Creil' },
            { id: 'Toulouse', name: 'Toulouse' },
        ]"
        :error="form.errors.ville"
        placeholder="Choisissez votre centre de formation"
    />
</div>

<!-- Genre and Date of birth below -->
<div class="grid md:grid-cols-2 gap-3 mt-3">
    <Select
        v-model="form.sexe"
        label="Genre"
        :show-search="false"
        :items="[
            { id: 'Homme', name: 'Homme' },
            { id: 'Femme', name: 'Femme' },
        ]"
        :error="form.errors.sexe"
        placeholder="Genre"
    />

    <DateField
        v-model="form.date_naissance"
        :error="form.errors.date_naissance"
        label="Date de naissance"
        birthday
    />
</div>

<!-- warning text about city selection -->
<div class="mt-4 p-4 rounded-lg border border-yellow-300 bg-yellow-50 text-yellow-800 text-sm leading-relaxed">
    <div class="flex items-start gap-3">
        <span class="text-lg mt-1">⚠️</span>
        <div class="space-y-2">
            <p class="font-semibold">
                Important – Choix de la ville
            </p>

            <p>
                Merci de bien sélectionner la ville la plus proche de chez vous lors de votre inscription.
            </p>

            <p>
                En cas d’erreur (choix d’une autre ville), vous ne pourrez pas planifier vos heures de conduite avec les enseignants rattachés à votre agence. Cela risque d’entraîner des retards dans votre planning et dans votre préparation à l’examen.
            </p>

            <p class="font-medium">
                👉 Vérifiez attentivement votre ville avant de valider votre inscription afin de bénéficier d’un suivi optimal avec la bonne équipe pédagogique.
            </p>
        </div>
    </div>
</div>

          <div class="flex gap-3">
    <div class="flex-1 hidden">
        <InputField 
            v-model="form.postal" 
            :error="form.errors.postal" 
            label="Postal" 
        />
    </div>

    <div class="flex-1">
        <Select
            v-model="form.ville"
            label="Ville"
            :show-search="false"
            :items="[
                { id: 'Creil', name: 'Creil' },
                { id: 'Toulouse', name: 'Toulouse' },
            ]"
            :error="form.errors.ville"
            placeholder="Ville"
        />
    </div>
</div>
                <InputField
                    v-model="form.postal_code_1"
                    :error="form.errors.postal_code_1"
                    label="Code postal 1"
                    placeholder="Entrez un second code postal si besoin"
                />


<br>

    <!-- Question: Do you have a NEPH number? -->
       <Select
      v-model="form.has_neph"
      label="Avez-vous un numéro NEPH ?"
      :items="[
        { id: 'Oui', name: 'Oui' },
        { id: 'Non', name: 'Non' },
      ]"
      placeholder="Sélectionnez une option"
    />

    <!-- ✅ Show input if "Oui" is selected -->
    <div v-if="showNephField">
      <InputField
        v-model="form.neph"
        :error="form.errors?.neph"
        label="Veuillez entrer votre numéro NEPH"
        placeholder=""
      />
    </div>


            <div class="grid md:grid-cols-2 gap-3">
                <DateField v-model="form.date_code" :error="form.errors.date_code" label="Date d’obtention du code de la route ?" />

                <Select
                    v-model="form.how_know"
                    :items="[
                        { id: 'Internet', name: 'Internet' },
                        { id: 'Publicité', name: 'Publicité' },
                        { id: 'Bouche à oreilles', name: 'Bouche à oreilles' },
                        { id: 'Flyers', name: 'Flyers' },
                        { id: 'Auto école', name: 'Auto école' },
                        { id: 'Autres', name: 'Autres' },
                    ]"
                    :show-search="false"
                    :error="form.errors.how_know"
                    label="Comment avez-vous connu PassPermisFacile ?"
                />
            </div>

            <InputField v-model="form.adresse" :multiline="2" :error="form.errors.adresse" label="Adresse complète" />
        </div>

        <div class="bg-white shadow-box rounded-xl p-5 space-y-3">
            <div class="grid md:grid-cols-2 gap-3">
                <InputField
                    id="password"
                    v-model="form.password"
                    :error="form.errors.password"
                    label="Mot de passe"
                    required
                    type="password"
                />
                <InputField
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    :error="form.errors.password_confirmation"
                    label="Mot de passe confirmation"
                    required
                    type="password"
                    @blur="checkPassword(form)"
                />
            </div>

            <div class="block text-sm">
                <p><span class="text-red-500">* </span> Champs obligatoires</p>
                <p>
                    En vous inscrivant, vous confirmez avoir lu et accepté le
                    <a href="/conditions-utilisation" class="underline hover:text-gray-700">conditions générales d’utilisation</a>.
                </p>
            </div>
        </div>

        <ClientButton class="mt-6 w-full text-center tracking-wide" type="submit" :disabled="!form.isDirty">
            S’inscrire maintenant
        </ClientButton>
    </form>
</template>
