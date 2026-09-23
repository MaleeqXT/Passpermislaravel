<script setup lang="ts">
import { useCart } from '@shared/stores';
import { moneyFormat } from '@shared/utils';
import Item from './Item.vue';
import { usePage } from '@inertiajs/vue3';
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';

const cart = useCart();
const page = usePage();

// Debug: log selectedInstallments and itemBalances whenever they change
import { watch } from 'vue';
watch(
  () => ({ sel: cart.selectedInstallments, bal: cart.itemBalances }),
  (v) => {
    try {
      console.log('DEBUG cart.selectedInstallments', JSON.parse(JSON.stringify(cart.selectedInstallments)));
      console.log('DEBUG cart.itemBalances', JSON.parse(JSON.stringify(cart.itemBalances)));
    } catch (e) {
      console.log('DEBUG cart state', cart.selectedInstallments, cart.itemBalances);
    }
  },
  { deep: true }
);


// Track open dropdowns
const openDropdowns = ref<Record<string | number, boolean>>({});
// Map of paid installments per offer: { [offerId]: { [installmentNo]: true } }
const paidInstallments = reactive<Record<string, Record<number, boolean>>>({});

// Sum of paid amounts per offer (to detect fully-paid total_payment)
const paidAmountPerOffer = reactive<Record<string, number>>({});

// Individual payments per offer to check if any single payment = full price
const paymentsPerOffer = reactive<Record<string, number[]>>({});

// Helper: Get original_price from agency_pricing
const getFullPriceAmount = (offer: any): number => {
  if (!offer?.agency_pricing) return 0;
  try {
    let pricingList = typeof offer.agency_pricing === 'string'
      ? JSON.parse(offer.agency_pricing)
      : offer.agency_pricing;
    if (!Array.isArray(pricingList) || !pricingList.length) return 0;

    const userAgency = getUserAgency();
    const pricing = pricingList.find((p: any) => String(p.agency ?? '').toLowerCase() === userAgency) ?? pricingList[0];
    return Number(pricing?.original_price || 0);
  } catch (e) {
    return 0;
  }
};

// Try fetching student sales to mark paid installments. Check if this student has
// previously purchased any of these offers and mark which installments are already paid.
const fetchStudentSales = async () => {
  try {
    // Only fetch if user is logged in
    const user = (page.props.auth as any)?.user;
    if (!user) {
      console.debug('🔴 User not logged in - skipping sales fetch');
      return;
    }

    const studentId = user?.student?.id;
    if (!studentId) {
      console.debug('🔴 No student ID found');
      return;
    }

    // Use the correct endpoint that returns sales with details
    const url = `/api/students/${studentId}/sales-with-details`;

    console.log('🟡 Fetching sales from:', url);

    const res = await axios.get(url);

    if (!res || !res.data) {
      console.debug('🔴 fetchStudentSales: No sales data returned');
      return;
    }

    console.log('🟡 RAW API RESPONSE:', res.data);
    const allSales = Array.isArray(res.data) ? res.data : res.data.data || res.data.sales || [];
    console.log('🟡 fetchStudentSales: ALL sales for student', studentId);
    console.log('🟡 allSales array:', allSales);
    console.log('🟡 allSales length:', allSales.length);
    if (allSales.length > 0) {
      const firstSale = allSales[0];
      console.log('🟡 FIRST SALE OBJECT KEYS:', Object.keys(firstSale));
      console.log('🟡 FIRST SALE offer_id field:', firstSale.offer_id);
      console.log('🟡 FIRST SALE cart field:', firstSale.cart);
      console.log('🟡 FIRST SALE FULL OBJECT:', JSON.stringify(firstSale, null, 2));
    }

    // Get current items in cart to check against historical purchases
    const currentOfferIds = cart.data.map((item: any) => String(item.offer?.id || item.offer_id || ''));
    console.log('🟡 currentOfferIds in cart:', currentOfferIds);

    // Filter: only paid sales from this student for offers in current cart
    const paidSalesForCurrentOffers = allSales.filter((s: any) => {
      const sStudentId = s.student_id;

      // Try to get offer_id from multiple possible locations, checking snake_case FIRST
      let offerId = '';

      // Check cart_details (snake_case - default from Laravel API)
      if (s.cart?.cart_details?.[0]?.offer_id) {
        offerId = String(s.cart.cart_details[0].offer_id);
      }
      // Check cart_details offer object
      else if (s.cart?.cart_details?.[0]?.offer?.id) {
        offerId = String(s.cart.cart_details[0].offer.id);
      }
      // Check camelCase fallback
      else if (s.cart?.cartDetails?.[0]?.offer_id) {
        offerId = String(s.cart.cartDetails[0].offer_id);
      }
      // Check camelCase offer object
      else if (s.cart?.cartDetails?.[0]?.offer?.id) {
        offerId = String(s.cart.cartDetails[0].offer.id);
      }
      // Fallback to direct fields
      else if (s.offer_id) {
        offerId = String(s.offer_id);
      }
      else if (s.offer?.id) {
        offerId = String(s.offer.id);
      }

      const rawStatus = s.payment_status || s.status || '';
      const statusStr = String(rawStatus).toLowerCase();

      // Check if status is 'paid' (string) or 2 (numeric for paid status) or 'completed' or 1
      const isPaid = statusStr === 'paid' || statusStr === 'completed' || rawStatus === 2 || rawStatus === 1 || rawStatus === '2' || rawStatus === '1';

      // Verify student matches (compare as strings, not numbers - these are UUIDs)
      const studentMatches = sStudentId && String(sStudentId) === String(studentId);
      const offerInCart = currentOfferIds.includes(offerId);

      console.log(`🟢 Checking sale - studentId: ${sStudentId}, offerId: ${offerId}, status: ${rawStatus} (isPaid: ${isPaid}), studentMatches: ${studentMatches}, offerInCart: ${offerInCart}`);

      // Include if: student matches AND status is paid AND offer is in current cart
      return studentMatches && isPaid && offerInCart;
    });

    console.log('🟡 Paid sales for current offers:', paidSalesForCurrentOffers);

    // For installments: Mark paid installments using exact installment_no from sales
    // If sale.installment_no is not present, try to read selected_installment_no from sale.cart.cart_details
    // Only fall back to cumulative logic for legacy sales where neither exist
    paidSalesForCurrentOffers.forEach((s: any) => {
      const offerId = String(
        s.offer_id ||
        s.offer?.id ||
        s.cart?.cartDetails?.[0]?.offer_id ||
        s.cart?.cartDetails?.[0]?.offer?.id ||
        s.cart?.cart_details?.[0]?.offer_id ||
        s.cart?.cart_details?.[0]?.offer?.id ||
        s.product?.offer_id ||
        s.product_id ||
        s.offerId ||
        ''
      );

      if (!offerId) return;

      // 1) If sale has exact installment_no field, use it directly
      if (s.installment_no !== null && s.installment_no !== undefined) {
        if (!paidInstallments[offerId]) paidInstallments[offerId] = {};
        const instNo = Number(s.installment_no);
        paidInstallments[offerId][instNo] = true;
        console.log(`🟢 Sale has installment_no=${instNo} for offer ${offerId} - marking ONLY that installment as paid`);
        return; // Done with this sale
      }

      // 2) Try to extract selected_installment_no from nested cart details (handles cases where sale.installment_no was not saved)
      const cartDetails = s.cart?.cart_details || s.cart?.cartDetails || [];
      if (Array.isArray(cartDetails) && cartDetails.length) {
        const match = cartDetails.find((cd: any) => {
          const cdOfferId = String(cd.offer_id || cd.offer?.id || cd?.offerId || '');
          return cdOfferId && String(cdOfferId) === String(offerId);
        });
        if (match) {
          const selInst = match.selected_installment_no ?? match.selectedInstallmentNo ?? null;
          if (selInst !== null && selInst !== undefined) {
            if (!paidInstallments[offerId]) paidInstallments[offerId] = {};
            paidInstallments[offerId][Number(selInst)] = true;
            console.log(`🟢 Sale cart detail has selected_installment_no=${selInst} for offer ${offerId} - marking ONLY that installment as paid`);
            return; // Done with this sale
          }
        }
      }

      // 3) Fallback to cumulative logic only for legacy sales without installment info
      const amount = Number(s.amount || s.paid_amount || 0);
      const key = offerId;

      // Always accumulate total paid amount AND track individual payment
      paidAmountPerOffer[key] = (paidAmountPerOffer[key] || 0) + (isNaN(amount) ? 0 : amount);

      // Track individual payments for later cumulative check
      if (!paymentsPerOffer[key]) paymentsPerOffer[key] = [];
      if (amount > 0) paymentsPerOffer[key].push(amount);
    });

    // For installments without direct installment_no, use cumulative amounts logic
    // BUT: Only mark as paid if multi_payment > 1 (i.e., it's an installment offer, not one-time)
    cart.data.forEach((item: any) => {
      const offerId = String(item.offer?.id || item.offer_id || '');
      if (!offerId) return;

      const installments = getInstallments(item.offer);
      if (!installments.length) return;

      // CRITICAL: Only mark installments if this offer has multiple payment options
      const multiPayment = item.offer?.multi_payment || installments.length;
      if (multiPayment <= 1) {
        console.log(`🔵 Offer ${offerId}: multi_payment = ${multiPayment} (not an installment offer, skip marking)`);
        return;
      }

      const totalPaidByStudent = paidAmountPerOffer[offerId] || 0;

      // If student hasn't paid anything for this offer, skip
      if (totalPaidByStudent <= 0) {
        console.log(`🔵 No legacy payments (without installment_no) for offer ${offerId}`);
        return;
      }

      // Get the original price (Prix complet) to exclude it from installment marking
      const fullPriceAmount = getFullPriceAmount(item.offer);
      const payments = paymentsPerOffer[offerId] || [];

      // Check if any SINGLE payment is the full price (prix complet)
      const hasPrixCompletPayment = payments.some((p: number) => Math.abs(p - fullPriceAmount) < 0.01 && fullPriceAmount > 0);

      if (hasPrixCompletPayment) {
        console.log(`🟡 Offer ${offerId}: Prix complet payment detected (${fullPriceAmount}€) - NOT marking installments`);
        return; // Skip installment marking for prix complet
      }

      console.log(`🟢 Processing offer ${offerId} (legacy): totalPaid=${totalPaidByStudent}, multiPayment=${multiPayment}, installments:`, installments);

      // First: try to match individual payments to single installment amounts
      // This avoids marking multiple installments when a single payment equals one installment
      const matchedAmounts = new Set<number>();
      payments.forEach((p: number) => {
        const match = installments.find((inst: any) => Math.abs(Number(inst.amount || 0) - p) < 0.5);
        if (match) {
          if (!paidInstallments[offerId]) paidInstallments[offerId] = {};
          paidInstallments[offerId][Number(match.no)] = true;
          matchedAmounts.add(Number(match.amount || 0));
          console.log(`🟢 Matched payment ${p} to installment ${match.no} for offer ${offerId}`);
        }
      });

      // Recompute remaining paid amount after marking matched installments
      const matchedSum = Array.from(matchedAmounts).reduce((s, v) => s + Number(v || 0), 0);
      let remainingPaid = totalPaidByStudent - matchedSum;

      // If anything remains, fall back to cumulative marking for the leftover amount
      if (remainingPaid > 0) {
        let cumulativeAmount = 0;
        installments.forEach((inst: any) => {
          const instAmount = Number(inst.amount || 0);
          // Skip if this installment was already marked via direct match
          if (paidInstallments[offerId] && paidInstallments[offerId][Number(inst.no)]) {
            cumulativeAmount += instAmount;
            return;
          }
          cumulativeAmount += instAmount;
          if (cumulativeAmount <= remainingPaid) {
            if (!paidInstallments[offerId]) paidInstallments[offerId] = {};
            paidInstallments[offerId][Number(inst.no)] = true;
            console.log(`🟢 Marking (cumulative) offer ${offerId}, installment ${inst.no} as PAID (cumulative: ${cumulativeAmount} <= ${remainingPaid})`);
          }
        });
      }
    });

    // Reset cycle: if ALL installments for an offer are fully paid, allow a fresh cycle (reset them)
    cart.data.forEach((item: any) => {
      const offerId = String(item.offer?.id || item.offer_id || '');
      if (!offerId) return;

      const installments = getInstallments(item.offer);
      if (!installments.length) return;

      // Check if ALL installments are marked as paid
      const allPaid = installments.every((inst: any) => {
        return paidInstallments[offerId] && paidInstallments[offerId][Number(inst.no)];
      });

      // If ALL installments are paid, reset them to allow a fresh selection cycle
      if (allPaid) {
        console.log(`🔄 All installments paid for offer ${offerId} - resetting for new cycle`);
        delete paidInstallments[offerId];
      }
    });

    console.log('🟢 Final paidInstallments (after reset):', JSON.parse(JSON.stringify(paidInstallments)));
    console.log('🟢 Final paidAmountPerOffer:', paidAmountPerOffer);

  } catch (e) {
    // Ignore errors — this feature is progressive enhancement
    console.debug('🔴 fetchStudentSales failed', e);
  }
};

onMounted(() => {
  fetchStudentSales();
});

// Toggle dropdown visibility
const toggleDropdown = (itemId: string | number) => {
    openDropdowns.value[itemId] = !openDropdowns.value[itemId];
};

// Close all dropdowns
const closeAllDropdowns = () => {
    openDropdowns.value = {};
};

// Get user's agency based on city (same as CartDrawer)
const getUserAgency = (): string => {
    const user = (page.props.auth as any)?.user;
    if (!user) return 'criel';

    const ville = String(user.ville ?? '').toLowerCase();

    if (ville.includes('creil')) return 'criel';
    if (ville.includes('toulouse')) return 'toulouse';

    return 'criel';
};

// Helper: Normalize and match agency names (handles criel/creil variations)
const findMatchingAgencyPricing = (pricingList: any[], userAgency: string) => {
    // Try exact match first
    let pricing = pricingList.find((p: any) => String(p.agency ?? '').toLowerCase() === userAgency);

    // If no exact match, try to match by normalizing spelling variations (criel/creil)
    if (!pricing) {
      pricing = pricingList.find((p: any) => {
        const agencyName = String(p.agency ?? '').toLowerCase();
        // Handle criel/creil variations - they should match each other
        if ((userAgency === 'criel' || userAgency === 'creil') && (agencyName === 'criel' || agencyName === 'creil')) {
          return true;
        }
        return agencyName === userAgency;
      });
    }

    // Return match or first element as fallback
    return pricing ?? pricingList[0];
};

const getInstallmentTotal = (offer: any) => {
    const installments = getInstallments(offer);
    if (!installments.length) return 0;

  return Math.floor(
    installments.reduce(
        (sum: number, inst: any) => sum + Number(inst.amount || 0),
        0
    )
);

};

// Helper: Get balance from agency_pricing for user's agency
const getAgencyBalance = (offer: any): number => {
  if (!offer?.agency_pricing) return 0;
  try {
    let pricingList = typeof offer.agency_pricing === 'string'
      ? JSON.parse(offer.agency_pricing)
      : offer.agency_pricing;
    if (!Array.isArray(pricingList) || !pricingList.length) return 0;

    const userAgency = getUserAgency();
    const pricing = findMatchingAgencyPricing(pricingList, userAgency);
    return Number(pricing?.balance || 0);
  } catch (e) {
    return 0;
  }
};

const getInstallmentBalances = (item: any) => {
  // Prefer any frontend override for balance (hours) stored in sessionStorage
  // First try agency_pricing, then offer balance
  let fullBalance = getAgencyBalance(item.offer) || Number(item.offer?.balance || 0);
  try {
    const overrides = JSON.parse(sessionStorage.getItem('cart_price_overrides') || '{}');
    const itemKey = String(item?.id ?? '');
    const offerKey = String(item?.offer?.id ?? '');
    const override = (itemKey && overrides[itemKey]) || overrides[offerKey];
    if (override && override.hours !== undefined && override.hours !== null) {
      fullBalance = Number(override.hours) || 0;
    }
  } catch (e) {
    // ignore parsing errors and keep original fullBalance
  }

  const installments = getInstallments(item.offer);
  const count = installments.length;

  if (!count || !fullBalance) return [];

  // Round to nearest integer to avoid floating-point precision issues
  fullBalance = Math.round(fullBalance);

  const base = Math.floor(fullBalance / count);
  const remainder = fullBalance - base * count;

  return installments.map((inst: any, index: number) => ({
    no: inst.no,
    balance: index === 0 ? base + remainder : base,
  }));
};


const getInstallmentBalanceFor = (item: any, no?: number) => {
  // Prefer precomputed balanceHours from cart.selectedInstallments (set by useCart.setSelectedInstallment)
  try {
    const sel = cart.selectedInstallments[item.id] || cart.selectedInstallments[item.offer?.id];
    if (sel && typeof sel.balanceHours === 'number' && sel.balanceHours >= 0) {
      return Math.round(sel.balanceHours);
    }
  } catch (e) {
    // ignore
  }

  const balances = getInstallmentBalances(item);
  // Prefer override if no installment split available
  try {
    const overrides = JSON.parse(sessionStorage.getItem('cart_price_overrides') || '{}');
    const itemKey = String(item?.id ?? '');
    const offerKey = String(item?.offer?.id ?? '');
    const override = (itemKey && overrides[itemKey]) || overrides[offerKey];
    if (!balances?.length && override && override.hours !== undefined && override.hours !== null) {
      return Math.round(Number(override.hours) || 0);
    }
  } catch (e) {
    // ignore
  }

  // If no computed balances, try to parse hours from the offer/item title (handles "Conduite Supervisée 2h" cases)
  if (!balances?.length) {
    const parsed = parseHoursFromTitle(item);
    if (parsed !== null && parsed !== undefined && parsed > 0) {
      return Math.round(parsed);
    }
    return Math.round(getAgencyBalance(item.offer) || item.offer?.balance || 0);
  }

  if (!no) return balances[0]?.balance ?? Math.round(getAgencyBalance(item.offer) || item.offer?.balance || 0);
  const found = balances.find((b: any) => Number(b.no) === Number(no));
  return found ? found.balance : balances[0]?.balance ?? Math.round(getAgencyBalance(item.offer) || item.offer?.balance || 0);
};

// Helper: extract hours from offer/item title (e.g. "Conduite Supervisée 2h" or "Conduite 2.5 h")
const parseHoursFromTitle = (item: any): number | null => {
  try {
    // gather all string values from item and its offer for scanning
    const collectText = (obj: any): string => {
      if (!obj) return '';
      if (typeof obj === 'string') return obj;
      if (Array.isArray(obj)) return obj.map(collectText).join(' ');
      if (typeof obj === 'object') return Object.values(obj).map(collectText).join(' ');
      return '';
    };

    let text = collectText(item);
    text = text.trim();
    if (!text) return null;

    // DEBUG: log the text being parsed so we can see edge cases
    console.debug('parseHoursFromTitle input:', text);

    // match patterns like '2h', '2 h', '2.5h', '2,5 h', allow any unicode whitespace before h
    const m = text.match(/(\d+(?:[.,]\d+)?)[\s\u00A0\u202f]*h/i);
    if (!m) {
      console.debug('parseHoursFromTitle no match');
      return null;
    }
    const num = m[1].replace(',', '.');
    const parsed = Number(num);
    if (isNaN(parsed)) {
      console.debug('parseHoursFromTitle parsed NaN from', num);
      return null;
    }
    const rounded = Math.round(parsed);
    console.debug('parseHoursFromTitle returning', rounded);
    return rounded;
  } catch (e) {
    console.debug('parseHoursFromTitle error', e);
    return null;
  }
};

// Helper: compute the balance shown in the UI (tries agency/offer balance then title parse)
const getDisplayBalance = (item: any): number => {
  try {
    // If user selected an installment, prefer that computed value
    const sel = cart.selectedInstallments[item.id] || cart.selectedInstallments[item.offer?.id];
    if (sel && sel.priceType === 'installment') {
      return getInstallmentBalanceFor(item, sel.installmentNo);
    }

    // Try agency or offer balance first
    const agency = Math.floor(getAgencyBalance(item.offer) || Number(item.offer?.balance || 0));
    if (agency && agency > 0) return agency;

    // Fallback: try to parse hours from the title (e.g. "Conduite Supervisée 2h")
    const parsed = parseHoursFromTitle(item);
    if (parsed !== null && parsed !== undefined && parsed > 0) return parsed;

    // nothing found, log for debugging
    console.debug('getDisplayBalance failed, item:', item);
    return 0;
  } catch (e) {
    console.debug('getDisplayBalance error', e, 'item', item);
    return 0;
  }
};

// Helper: Get agency pricing for an offer
const getAgencyPricing = (offer: any) => {
    if (!offer?.agency_pricing) return null;
    let pricingList = typeof offer.agency_pricing === 'string'
        ? JSON.parse(offer.agency_pricing)
        : offer.agency_pricing;

    if (!Array.isArray(pricingList) || !pricingList.length) return null;

    const userAgency = getUserAgency();
    return findMatchingAgencyPricing(pricingList, userAgency);
};

// Helper: Get installments array
const getInstallments = (offer: any) => {
    const pricing = getAgencyPricing(offer);
    return pricing?.installments || [];
};

// Helper: prefer override label/service_label for display name
const getItemDisplayName = (item: any): string => {
  try {
    const overrides = JSON.parse(sessionStorage.getItem('cart_price_overrides') || '{}');
    const itemKey = String(item?.id ?? '');
    const offerId = String(item?.offer?.id ?? '');
    const override = (itemKey && overrides[itemKey]) || overrides[offerId];
    if (override) {
      if (override.service_label) return String(override.service_label);
      if (override.label) return String(override.label);
    }
  } catch (e) {
    // ignore
  }
  return item.offer?.name || item.name || 'Produit';
};

// Helper to check if an installment for an offer is already paid
const isInstallmentPaid = (offerId: any, instNo: number) => {
  if (!offerId) return false;
  const key = String(offerId);
  return !!(paidInstallments[key] && paidInstallments[key][Number(instNo)]);
};

</script>
<template>
    <article class="mx-auto max-w-2xl lg:pb-24 lg:pt-16 sm:px-6 lg:max-w-2xl lg:px-8 w-full lg:w-1/3 lg:sticky lg:top-0 z-10 lg:h-fit">
        <h2 class="text-md lg:text-lg font-semibold text-dark mb-2 lg:mb-6">Récapitulatif de la Commande</h2>
  <ul role="list" class="space-y-4 mb-4">
  <li v-for="item in cart.data" :key="item.id" class="flex flex-col border-b pb-3">
    <div class="flex justify-between mb-2">
      <span class="font-medium">{{ getItemDisplayName(item) }}</span>
      <div class="flex flex-col items-end">
        <span class="font-bold">{{ moneyFormat(cart.itemPrices[item.id] || 0) }}</span>
<span class="text-xs text-gray-500">
  Balance: {{ getDisplayBalance(item) }}h
</span>


      </div>
    </div>

    <!-- Installment Selection UI (Dropdown) -->
    <!-- Show if: multi_payment > 1 AND installments exist. Even when fully paid, keep UI visible (options will be disabled). -->
    <div v-if="item.offer?.multi_payment > 1 && getInstallments(item.offer).length > 0" class="mt-2 space-y-2">
      <p class="text-xs font-semibold text-gray-700">Choisir le mode de paiement</p>

      <!-- Buttons Row -->
      <div class="flex gap-2">
        <!-- Prix Complet Button -->
        <button
          class="flex-1 px-2 py-1 text-xs font-semibold rounded border-2 transition-all"
          :class="cart.selectedInstallments[item.id]?.priceType !== 'installment'
            ? 'bg-blue-600 text-white border-blue-600'
            : 'bg-white text-blue-600 border-blue-300 hover:bg-blue-50'"
          @click="cart.setSelectedInstallment(item.id, null, 'original')"
        >
          Prix complet: {{ moneyFormat(getAgencyPricing(item.offer)?.original_price || cart.resolveOfferPrice(item.offer)) }}
        </button>

        <!-- Dropdown Button -->
        <div class="flex-1 relative">
          <button
            @click="toggleDropdown(item.id)"
            class="w-full px-2 py-1 text-xs font-semibold rounded border-2 text-left flex justify-between items-center transition-all"
            :class="cart.selectedInstallments[item.id]?.priceType === 'installment'
              ? 'bg-blue-500 text-white border-blue-500 hover:bg-blue-600'
              : 'bg-white text-blue-600 border-blue-300 hover:bg-blue-50'"
          >
            <span class="truncate">
              {{
                cart.selectedInstallments[item.id]?.priceType === 'installment'
                  ? `Paiement en ${cart.selectedInstallments[item.id]?.installmentNo}`
                  : 'Paiement en fois'
              }}
            </span>
            <span :class="['ml-1 transition-transform flex-shrink-0', openDropdowns[item.id] ? 'rotate-180' : '']">▼</span>
          </button>

          <!-- Dropdown Menu -->
          <div v-if="openDropdowns[item.id]" @click.stop class="absolute top-full left-0 right-0 mt-1 bg-white border border-gray-300 rounded shadow-lg z-10 overflow-hidden">
            <!-- Installment Options Only -->
            <button
              v-for="inst in getInstallments(item.offer)"
              :key="inst.no"
              @click="() => { const alreadySelected = cart.selectedInstallments[item.id]?.installmentNo === inst.no; if (!isInstallmentPaid(item.offer?.id, inst.no) && !alreadySelected) { cart.setSelectedInstallment(item.id, inst.no, 'installment'); } }"
              class="w-full px-2 py-1 text-xs text-left hover:bg-gray-100 transition-all border-b last:border-b-0"
              :disabled="isInstallmentPaid(item.offer?.id, inst.no) || cart.selectedInstallments[item.id]?.installmentNo === inst.no"
              :class="(cart.selectedInstallments[item.id]?.priceType === 'installment' && cart.selectedInstallments[item.id]?.installmentNo === inst.no)
                ? 'bg-blue-50 text-blue-600 font-semibold'
                : (isInstallmentPaid(item.offer?.id, inst.no) ? 'bg-gray-50 text-gray-400 line-through' : 'text-gray-700')"
            >
            <div class="flex justify-between">
  <span>
    Paiement {{ inst.no }}/{{ item.offer?.multi_payment }}
    <span v-if="isInstallmentPaid(item.offer?.id, inst.no)" class="ml-2 text-2xs text-green-600">(Payé)</span>
  </span>
  <span>{{ moneyFormat(inst.amount) }}</span>
</div>


            </button>
          </div>
        </div>
      </div>

      <!-- Installment Summary -->
      <div v-if="cart.selectedInstallments[item.id]?.priceType === 'installment'" class="mt-2 p-2 bg-blue-50 rounded text-xs">
        <div class="flex justify-between mb-1">
          <span class="text-gray-700">Prix par tranche:</span>
          <span class="font-semibold">{{ moneyFormat(cart.itemPrices[item.id] || 0) }}</span>
        </div>
        <div class="flex justify-between mb-1">
          <span class="text-gray-700">Nombre de tranches:</span>
          <span class="font-semibold">{{ item.offer?.multi_payment || getInstallments(item.offer).length }} fois</span>
        </div>
        <div class="flex justify-between border-t pt-1 mt-1 mb-1">
          <span class="text-gray-700 font-semibold">Total Prix:</span>
          <span class="font-semibold text-blue-600">
  {{ moneyFormat(getInstallmentTotal(item.offer)) }}
</span>

        </div>
        <div class="flex justify-between">

        </div>
      </div>
    </div>
  </li>
</ul>



        <dl class="space-y-2 lg:space-y-6 border-t border-gray-200 py-3 lg:py-6">
            <div class="flex justify-between">
                <dt class="text-sm">Paiement sur tranches</dt>
                <dd class="font-medium text-dark">
                    {{ cart.state.isSpliteActive ? cart.getTranche() || 1 : 'Standard (Une fois)' }}
                </dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-sm">Sous-total</dt>
                <dd class="font-medium text-dark">{{ moneyFormat(cart.prices.subTotal) }}</dd>
            </div>

            <!-- <span class="font-light text-md">{{ cart.count }} Produits</span> -->
            <div class="flex items-center justify-between border-t border-gray-200 pt-2 lg:pt-6">
                <dt class="text-lg font-medium">Total à payer</dt>
                <dd class="text-lg font-medium text-dark">{{ moneyFormat(cart.prices.total) }}</dd>
            </div>
            <!-- Installment notice -->
            <div v-if="cart.getTranche()" class="text-2xs text-gray-500 mt-1">
                <p>Paiement en {{ cart.getTranche() }} fois sans frais par carte bancaire ou paypal,</p>
                <p class="mt-1">paiement par prélèvement manuel tous les mois sur votre espace</p>
            </div>
        </dl>
    </article>
</template>

