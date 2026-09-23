<script setup lang="ts">
import { Button } from '@shared/components';
import { LineProgress } from '@common/components';
import { CartIcon, LightbulbIcon } from '@adersolutions/icons';
import { strip } from '@shared/utils';
import { routes } from '@espace-student/routes';
import { useStudentSpace } from '@espace-student/stores';
import { useCart, useApp } from '@shared/stores';
import { computed, onMounted, ref } from 'vue';
// Ziggy `route()` is exposed globally; declare for TS to avoid template type errors
declare const route: (name: string, params?: any, absolute?: boolean) => string;

const { stats, user } = useStudentSpace();
const cart = useCart();
const { user: appUser } = useApp();

// Ensure cart data is fetched when dashboard loads
onMounted(() => {
    if (cart.getAll) {
        try { cart.getAll(); } catch (e) { /* ignore */ }
        return;
    }
    if (cart.query && cart.query.fetch) cart.query.fetch();
});

const percentage = computed(() => {
    const { total, done } = stats.data.competences || { total: 0, done: 0 };
    if (!total || !done) return 0;
    return strip((done / total) * 100);
});

const canSeeOffers = computed(() => {
  // Disable offers if popup should be shown
  return !shouldShowPopup.value;
});

const shouldShowPopup = computed(() => {
  if (appUser) {
    const ville = String(appUser.ville || '').toLowerCase().trim();
    const postal = String(appUser.postal || '').trim();

    // Check if either ville or postal code is missing or incorrect
    const isVilleMissingOrIncorrect = !ville || (!ville.includes('creil') && !ville.includes('toulouse'));
    const isPostalMissingOrIncorrect = !postal || (postal !== '60100' && postal !== '31300');

    return isVilleMissingOrIncorrect || isPostalMissingOrIncorrect;
  }
  return true;
});

const isButtonDisabled = computed(() => {
  if (appUser) {
    const ville = String(appUser.ville || '').toLowerCase().trim();
    const postal = String(appUser.postal || '').trim();

    // Check if both ville and postal code are valid
    const isVilleValid = ville.includes('creil') || ville.includes('toulouse');
    const isPostalValid = postal === '60100' || postal === '31300';

    // Disable if either ville or postal is invalid
    return !(isVilleValid && isPostalValid);
  }
  return true; // Disable if appUser is not available
});

// Display the student's DB-stored balance only (fallback to server stats)
// The requirement is to show exactly the balance stored in DB, not session/cart previews.
const displayBalance = computed(() => {
    try {
        const dbBalance = Number(user?.student?.balance ?? NaN);
        const currentBalance = !Number.isNaN(dbBalance) ? dbBalance : Number(stats.data.balance?.rest || 0);
        console.log(`🎯 displayBalance (DB-preferred): ${currentBalance}h`);
        return currentBalance;
    } catch (e) {
        console.error('❌ displayBalance error:', e);
        return stats.data.balance?.rest || 0;
    }
});

// Compute pending balance added by currently selected installments in cart
// Fallbacks to preview items and persisted selections when server cart is empty
const pendingInstallmentBalance = computed(() => {
    try {
        // prefer server cart data
        let items = cart.data || [];
        if (!items || !items.length) {
            // try persisted previews
            try {
                const previews = JSON.parse(sessionStorage.getItem('cart_preview_items') || '{}');
                items = Object.values(previews || {});
            } catch (e) {
                items = [];
            }
        }

        if (!items || !items.length) {
            return 0;
        }

        // persisted selections map
        let persistedSel: Record<string, any> = {};
        try {
            persistedSel = JSON.parse(sessionStorage.getItem('cart_selected_installments') || '{}');
        } catch (e) {
            persistedSel = {};
        }

        let total = 0;
        console.log('🔍 pendingInstallmentBalance START: items count =', items.length);

        for (const item of items) {
            // selection: reactive store first, then persisted
            let sel = cart.selectedInstallments[String(item.id)] || cart.selectedInstallments[String(item?.offer?.id)];
            if (!sel) sel = persistedSel[String(item.id)] || persistedSel[String(item?.offer?.id)];

            // If there's no explicit selection for this item, skip it (don't assume full payment)
            if (!sel) {
                console.log(`   → item ${item.id}: no explicit selection → SKIP (no change)`);
                continue;
            }

            // ✅ Use the precomputed balance stored in the selection (set by setSelectedInstallment)
            const balanceHours = typeof sel.balanceHours === 'number' ? sel.balanceHours : 0;

            console.log(`   → item ${item.id}: priceType=${sel.priceType}, balanceHours=${balanceHours} → ADD ${balanceHours}h, total now = ${total + balanceHours}h`);
            total += balanceHours;
        }

        console.log('✅ pendingInstallmentBalance FINAL TOTAL:', total);
        return total;
    } catch (e) {
        console.error('❌ pendingInstallmentBalance error:', e);
        return 0;
    }
});

const showPopup = ref(true);

function closePopup() {
  showPopup.value = false;
}

function handleCommanderClick() {
  if (appUser) {
    const ville = String(appUser.ville || '').toLowerCase().trim();
    const postal = String(appUser.postal || '').trim();

    const isVilleValid = ville.includes('creil') || ville.includes('toulouse');
    const isPostalValid = postal === '60100' || postal === '31300';

    if (!isVilleValid || !isPostalValid) {
      alert('ACCESS DENIED. THE SHOP IS ONLY AVAILABLE FOR STUDENTS IN CREIL (60100) AND TOULOUSE (31300).');
      return;
    }
  }

  // Redirect to the shop if validation passes
  window.location.href = route(routes.shop.index);
}
</script>

<template>
    <section class="mt-4 mb-3 text-white px-3">
        <ul class="h-min overflow-hidden space-y-2">
            <li class="flex justify-between items-center gap-3">
                <dl class="flex-center gap-0.5">
                    <dd class="text-xs px-2 flex-center h-8 gap-1 bg-green-500/20 text-green-500 rounded-l-lg">
                        <span>Balance: </span>
                        <span class="font-bold text-md">{{ displayBalance }}h</span>
                    </dd>
                    <dd class="text-xs px-2 flex-center h-8 gap-1 bg-red-500/20 text-red-500 rounded-r-lg">
                        <span>Utilisé: </span>
                        <span class="font-bold text-md">{{ stats.data.balance.used }}h </span>
                    </dd>
                </dl>
                <Button
                  :disabled="isButtonDisabled"
                  variant="primary"
                  :icon="CartIcon"
                  @click="handleCommanderClick"
                >Commander</Button>
            </li>
            <li class="flex flex-col gap-4 bg-dark-block p-3">
                <div class="flex justify-between items-center gap-3">
                    <p class="text-base font-medium flex gap-2">
                        <LightbulbIcon class="w-7 p-1 bg-white/10 rounded-lg" />
                        Progrès
                    </p>
                    <Button link :href="route(routes.competences.index)" class="!p-0">
                        Voir carnet
                        <span> &rarr; </span>
                    </Button>
                </div>
                <LineProgress :done="stats.data.competences.done || 0" :total="stats.data.competences.total || 0" />
                <p class="text-xs">Votre Progrès de formation est {{ percentage }}%</p>
            </li>
        </ul>
    </section>

    <div v-if="showPopup && shouldShowPopup" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50">
      <div class="bg-white p-6 rounded shadow-md text-center">
        <h2 class="text-xl font-bold mb-4 text-dark">
          Notre site internet a été mis à jour. Merci de bien choisir l’agence dans laquelle vous êtes inscrit dans vos infos personnelles avant de prendre un forfait, afin de pouvoir planifier vos heures de conduite avec les bons enseignants.
        </h2>
        <div class="flex justify-center gap-4">
          <button @click="closePopup" class="bg-blue-500 text-white px-6 py-3 rounded-lg shadow-lg hover:bg-blue-600 transition-all duration-300">Fermer</button>
          <a href="/student/profile" class="bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg hover:bg-green-600 transition-all duration-300">Voir le profil</a>
        </div>
      </div>
    </div>
</template>
