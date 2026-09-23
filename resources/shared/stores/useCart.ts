import { computed, onMounted, reactive } from 'vue';
import { StorageTypeEnum } from '@common/enums';
import { useMutation, useQuery, useStorage } from '@shared/hooks';
import { routes } from '@espace-client/routes';
import { defineStore } from 'pinia';
import { usePage } from '@inertiajs/vue3';
import type { CommandeType, OfferType, UserType } from '@common/types';

const api = {
    index: 'api.carts.index',
    delete: 'api.carts.destroy',
    storeOrUpdate: 'api.carts.storeOrUpdate',
    storeOrUpdateMany: 'api.carts.storeOrUpdateMany',
    update: 'api.carts.updateCartDetail',
};

// --- helper functions -------------------------------------------------------

/**
 * Parse hours from a menu offer title.  Menu names often contain an hours
 * indicator such as "2h", "(2h)", "2 h" or even "2,5h".  We surface the
 * value so that zero‑balance menu cards still contribute to the cart total.
 *
 * This implementation mirrors logic in the checkout component and is used by
 * both display code and business logic (pricing/balances).
 */
const parseHoursFromTitle = (text: string | null | undefined): number | null => {
    if (!text) return null;
    try {
        // match a number followed optionally by whitespace then the letter 'h'
        const match = text.match(/(\d+(?:[\.,]\d+)?)\s*h/i);
        if (!match) {
            console.debug('parseHoursFromTitle no match');
            return null;
        }
        const num = parseFloat(match[1].replace(',', '.'));
        if (isNaN(num)) {
            console.debug('parseHoursFromTitle parsed NaN from', match[1]);
            return null;
        }
        const rounded = Math.round(num * 100) / 100;
        console.debug('parseHoursFromTitle returning', rounded);
        return rounded;
    } catch (e) {
        console.debug('parseHoursFromTitle error', e);
        return null;
    }
};

type StateType = {
    authModal: boolean;
    loading: Record<string, boolean>;
    successMsg: string;
    sale: CommandeType | null;
    notify: boolean;
    params: {
        type: string;
    };
    isSpliteActive: boolean;
};

export const useCart = (uid?: number) =>



    defineStore('Cart', () => {
        // ✅ Local storage cart for guest users
        const [local, setLocal] = useStorage<any[]>('CART_ITEMS', [], StorageTypeEnum.SESSION);

        const state = reactive<StateType>({
            authModal: false,
            loading: {},
            successMsg: '',
            sale: null,
            notify: true,
            params: { type: '' },
            isSpliteActive: false,
        });

        // 🆕 Track selected installment and price type per item
        const selectedInstallments = reactive<Record<string, { installmentNo?: number; priceType: 'original' | 'installment'; balanceHours?: number }>>({});

        // Load persisted selected installments from sessionStorage ONLY on checkout page
        // Other pages should not show installment selections from previous checkouts
        if (typeof window !== 'undefined' && window.location.pathname.includes('/checkout')) {
            try {
                const persisted = JSON.parse(sessionStorage.getItem('cart_selected_installments') || '{}');
                if (persisted && typeof persisted === 'object') {
                    Object.keys(persisted).forEach((k) => {
                        selectedInstallments[k] = persisted[k];
                    });
                }
            } catch (e) {
                // ignore
            }
        } else {
            // 🔥 NOT on checkout page: clear persisted selections to prevent stale data from previous checkouts
            try {
                sessionStorage.removeItem('cart_selected_installments');
                // Also clear from reactive store
                Object.keys(selectedInstallments).forEach(k => {
                    delete selectedInstallments[k];
                });
            } catch (e) {
                // ignore
            }
        }

        const loggedUser = computed<UserType>(
            () => (usePage().props.auth as { user: UserType }).user
        );

        const mutation = useMutation({});
        const query = useQuery({
            key: 'cart_details',
            url: route(api.index, uid),
        });


const getAgencyPricing = (offer: any) => {
    const page = usePage();

    let pricingList = offer.agency_pricing;

    // 🔥 FIX: parse JSON string
    if (typeof pricingList === 'string') {
        try {
            pricingList = JSON.parse(pricingList);
        } catch (e) {
            console.error('Invalid agency_pricing JSON', pricingList);
            return null;
        }
    }

    if (!Array.isArray(pricingList) || !pricingList.length) return null;

    // 🟢 guest fallback
    if (!page.props.auth?.user) {
        return pricingList[0];
    }

    let agency = '';
    const ville = String(page.props.auth.user.ville ?? '').toLowerCase();

    if (ville.includes('creil')) agency = 'criel';
    if (ville.includes('toulouse')) agency = 'toulouse';

    return (
        pricingList.find(
            (p: any) =>
                String(p.agency ?? '').toLowerCase() === agency
        ) ?? pricingList[0]
    );
};

    const resolveOfferPrice = (offer: any, installmentNo: number | null = null) => {
    if (!offer) return 0;

    const pricing = getAgencyPricing(offer);

    // 👉 INSTALLMENT PRICE (if specified)
    if (installmentNo && pricing?.installments?.length) {
        const installment = pricing.installments.find(
            (i: any) => Number(i.no) === installmentNo
        );

        if (installment?.amount) {
            return parseFloat(installment.amount) || 0;
        }
    }

    // ✅ INSTALLMENT SELECTED IN OFFER → SHOW THAT PRICE
    if (
        offer.selected_price_type === 'installment' &&
        offer.selected_installment_no &&
        pricing?.installments?.length
    ) {
        const installment = pricing.installments.find(
            (i: any) => Number(i.no) === Number(offer.selected_installment_no)
        );

        if (installment?.amount) {
            return parseFloat(installment.amount) || 0;
        }
    }

    // ✅ ORIGINAL PRICE (fallback)
    let price =
        pricing?.original_price ??
        pricing?.price_ht ??
        pricing?.total_payment ??
        offer.final_price ??
        offer.original_price ??
        offer.price_ht ??
        0;

    price = parseFloat(price as any) || 0;

    // 🔁 Fallback: if price is still zero we may be dealing with a "menu"
    // card whose `caracteristiques` field contains component euro values
    // but no direct numeric price.  Replicate backend behaviour by pulling
    // the largest euro amount (or single value) from the HTML blob.
    if (!price && (offer.caracteristiques || pricing?.caracteristiques)) {
        const caracSource = offer.caracteristiques || pricing?.caracteristiques || '';
        try {
            const matches = Array.from(caracSource.matchAll(/(\d+[\.,]?\d*)\s*(?:&nbsp;|\s)*€/gu));
            if (matches.length) {
                const nums = matches.map(m => parseFloat(m[1].replace(',', '.')) || 0);
                if (nums.length === 1) {
                    price = nums[0];
                } else if (nums.length > 1) {
                    price = Math.max(...nums);
                }
                console.log('🔍 resolveOfferPrice fallback extracted', price, 'from caracteristiques', caracSource);
            }
        } catch (e) {
            console.warn('resolveOfferPrice fallback parse failed', e);
        }
    }

    return price;
};





    const normalizeItem = (item: any) => {
            // item might be:
            // - an offer object stored locally (has id, final_price, second_price, ...)
            // - a server cart row { id: <cartId>, offer: {...}, selected_price_type: 'second' }
            const offerObj = item.offer ?? item;
            const offerId = offerObj.id ?? item.offer_id ?? item.offerId;
            const selected = item.selected_price_type ?? offerObj.selected_price_type ?? 'final';
            const quantity = item.quantity ?? 1;
            const tranches = item.tranches ?? offerObj.multi_payment ?? 1;
             return {
        offerObj,
        offerId,
       selected:
    selected === 'original'
        ? 'original'
        : selected === 'second'
            ? 'second'
            : 'final',


        quantity,
        tranches,
        raw: item,
    };
        };

        const data = computed(() => (loggedUser.value ? query.data : local.value) || []);
        const count = computed<number>(() => (data.value?.length || 0) as number);

        // Prices: respect selected_price_type per item and installments
     const prices = computed(() => {
    // Read any frontend overrides persisted in sessionStorage (keyed by offer id)
    let overrides: Record<string, { price: number; label?: string }> = {};
    try {
        overrides = JSON.parse(sessionStorage.getItem('cart_price_overrides') || '{}');
    } catch (e) {
        overrides = {};
    }

    const subTotal = data.value.reduce((acc: number, item: any) => {
        const { offerObj, quantity, tranches } = normalizeItem(item);
        const mp = state.isSpliteActive ? (tranches || 1) : 1;

        // 🆕 Check selected installment for this item (by item.id or offer id)
        const itemKey = String(item.id ?? '');
        const offerKey = String(offerObj.id ?? item.offer_id ?? item.offerId);
        const selectedForItem = selectedInstallments[itemKey] || selectedInstallments[offerKey];

        // ✅ Prefer a session override for the specific cart item id, then fallback to offer id
        const override = (itemKey && overrides[itemKey]) || overrides[offerKey];
        let price = 0;

        if (override && typeof override.price === 'number' && override.price > 0) {
            price = Number(override.price);
        } else if (selectedForItem?.priceType === 'installment' && selectedForItem.installmentNo) {
            price = resolveOfferPrice(offerObj, selectedForItem.installmentNo);
        } else {
            // Use explicit original price from agency_pricing or fallbacks
            const pricing = getAgencyPricing(offerObj);
            const origPrice =
                pricing?.original_price ??
                pricing?.price_ht ??
                pricing?.total_payment ??
                offerObj.final_price ??
                offerObj.original_price ??
                offerObj.price_ht ??
                0;
            price = parseFloat(origPrice as any) || 0;
        }

        return acc + (price * quantity) / mp;
    }, 0);

    // Load overrides to allow per-item balance overrides (stored in sessionStorage by add())
    let _overrides: Record<string, { price?: number; label?: string; hours?: number }> = {};
    try {
        _overrides = JSON.parse(sessionStorage.getItem('cart_price_overrides') || '{}');
    } catch (e) {
        _overrides = {};
    }

    const balance = data.value.reduce((acc: number, item: any) => {
        const { offerObj, quantity } = normalizeItem(item);
        const itemKey = String(item.id ?? '');
        const offerKey = String(offerObj.id ?? item.offer_id ?? item.offerId);
        const selectedForItem = selectedInstallments[itemKey] || selectedInstallments[offerKey];
        const pricing = getAgencyPricing(offerObj);
        const multiPayment = Number(pricing?.multi_payment ?? offerObj.multi_payment ?? 1);

        // Prefer override hours (by item id, then offer id)
        const override = (itemKey && _overrides[itemKey]) || _overrides[offerKey];

        // prefer balance from agency pricing when defined
        let itemBalance = Number(pricing?.balance ?? offerObj.balance ?? 0);
        if (override && typeof override.hours === 'number' && override.hours > 0) {
            itemBalance = Number(override.hours);
        }

        // Fallback: if still zero/empty try parsing the offer title (menu card case)
        if (!itemBalance) {
            const parsed = parseHoursFromTitle(offerObj.name ?? offerObj.service_label ?? '');
            if (parsed !== null) {
                itemBalance = parsed;
                console.log(`🛠 prices.balance fallback parsed ${parsed}h for item ${itemKey}`);
            }
        }

        // 🔥 If an installment price is selected, prefer precomputed balanceHours
        if (selectedForItem?.priceType === 'installment' && selectedForItem.installmentNo) {
            const precomputed = Number(selectedForItem.balanceHours || 0);
            if (precomputed > 0) {
                itemBalance = precomputed;
            } else {
                // Fallback: compute integer split with remainder added to first installment
                itemBalance = Math.round(itemBalance);
                const installmentNo = Number(selectedForItem.installmentNo);
                const base = Math.floor(itemBalance / multiPayment);
                const remainder = itemBalance - base * multiPayment;
                itemBalance = installmentNo === 1 ? base + remainder : base;
            }
        }

        return acc + itemBalance * quantity;
    }, 0);

    return {
        subTotal,
        balance,
        total: subTotal,
    };
});

        // 🆕 Individual item prices (cached for direct access in templates)
        const itemPrices = computed(() => {
            const prices: Record<string, number> = {};

            // Load overrides from sessionStorage
            let overrides: Record<string, { price: number; label?: string }> = {};
            try {
                overrides = JSON.parse(sessionStorage.getItem('cart_price_overrides') || '{}');
            } catch (e) {
                overrides = {};
            }

            data.value.forEach((item: any) => {
                const { offerObj } = normalizeItem(item);

                const itemIdStr = String(item.id);
                const offerIdStr = String(offerObj.id ?? item.offer_id ?? item.offerId);

                const selectedForItem = selectedInstallments[itemIdStr] || selectedInstallments[offerIdStr];

                // Prefer session override by item id, then offer id
                const override = (itemIdStr && overrides[itemIdStr]) || overrides[offerIdStr];
                let price = 0;

                if (override && typeof override.price === 'number' && override.price > 0) {
                    price = Number(override.price);
                } else if (selectedForItem?.priceType === 'installment' && selectedForItem.installmentNo) {
                    price = resolveOfferPrice(offerObj, selectedForItem.installmentNo);
                    console.log(`📊 itemPrices: Item ${itemIdStr} selected installment ${selectedForItem.installmentNo} = ${price}`);
                } else if (selectedForItem?.priceType === 'original') {
                    const pricing = getAgencyPricing(offerObj);
                    const origPrice =
                        pricing?.original_price ??
                        pricing?.price_ht ??
                        pricing?.total_payment ??
                        offerObj.final_price ??
                        offerObj.original_price ??
                        offerObj.price_ht ??
                        0;
                    price = parseFloat(origPrice as any) || 0;
                    // fallback when price remains zero
                    if (!price && (offerObj.caracteristiques || pricing?.caracteristiques)) {
                        const caracSource = offerObj.caracteristiques || pricing?.caracteristiques || '';
                        try {
                            const matches = Array.from(caracSource.matchAll(/(\d+[\.,]?\d*)\s*(?:&nbsp;|\s)*€/gu));
                            if (matches.length) {
                                const nums = matches.map(m => parseFloat(m[1].replace(',', '.')) || 0);
                                price = nums.length === 1 ? nums[0] : Math.max(...nums);
                                console.log('🔍 itemPrices fallback selected original', price);
                            }
                        } catch (e) {
                            console.warn('itemPrices fallback parse failed', e);
                        }
                    }
                    console.log(`📊 itemPrices: Item ${itemIdStr} selected original = ${price}`);
                } else {
                    const pricing = getAgencyPricing(offerObj);
                    const origPrice =
                        pricing?.original_price ??
                        pricing?.price_ht ??
                        pricing?.total_payment ??
                        offerObj.final_price ??
                        offerObj.original_price ??
                        offerObj.price_ht ??
                        0;
                    price = parseFloat(origPrice as any) || 0;
                    // fallback same as above
                    if (!price && (offerObj.caracteristiques || pricing?.caracteristiques)) {
                        const caracSource = offerObj.caracteristiques || pricing?.caracteristiques || '';
                        try {
                            const matches = Array.from(caracSource.matchAll(/(\d+[\.,]?\d*)\s*(?:&nbsp;|\s)*€/gu));
                            if (matches.length) {
                                const nums = matches.map(m => parseFloat(m[1].replace(',', '.')) || 0);
                                price = nums.length === 1 ? nums[0] : Math.max(...nums);
                                console.log('🔍 itemPrices fallback no selection', price);
                            }
                        } catch (e) {
                            console.warn('itemPrices fallback parse failed', e);
                        }
                    }
                    console.log(`📊 itemPrices: Item ${itemIdStr} no selection, using original = ${price}`);
                }

                prices[itemIdStr] = price;
            });

            console.log('🎯 itemPrices computed:', prices);
            console.log('📌 selectedInstallments state:', selectedInstallments);
            return prices;
        });

        // 🆕 Individual item balances (divided by installments if selected)
        const itemBalances = computed(() => {
            const balances: Record<string, number> = {};

            // Load overrides from sessionStorage for hours
            let overrides: Record<string, { price?: number; label?: string; hours?: number }> = {};
            try {
                overrides = JSON.parse(sessionStorage.getItem('cart_price_overrides') || '{}');
            } catch (e) {
                overrides = {};
            }

            data.value.forEach((item: any) => {
                const { offerObj } = normalizeItem(item);
                const itemIdStr = String(item.id);
                const offerIdStr = String(offerObj.id ?? item.offer_id ?? item.offerId);

                const selectedForItem = selectedInstallments[itemIdStr] || selectedInstallments[offerIdStr];
                const pricing = getAgencyPricing(offerObj);
                const multiPayment = Number(pricing?.multi_payment ?? offerObj.multi_payment ?? 1);

                // Prefer override hours by item id, then offer id
                const override = (itemIdStr && overrides[itemIdStr]) || overrides[offerIdStr];
                let balance = Number(offerObj.balance ?? 0);

                // Use override hours if available
                if (override && typeof override.hours === 'number' && override.hours >= 0) {
                    balance = Number(override.hours);
                }

                // Fallback: if we still have zero balance and the offer title contains
                // an hours indicator like "(2h)" parse that and use it.
                if (!balance) {
                    const parsed = parseHoursFromTitle(offerObj.name ?? offerObj.service_label ?? '');
                    if (parsed !== null) {
                        balance = parsed;
                        console.log(`💡 itemBalances: Item ${itemIdStr} parsed hours from title = ${balance}`);
                    }
                }

                // 🔥 Calculate balance for specific installment if installment is selected
                if (selectedForItem?.priceType === 'installment' && selectedForItem.installmentNo) {
                    // Prefer precomputed balanceHours saved by `setSelectedInstallment`
                    const precomputed = Number(selectedForItem.balanceHours || 0);
                    if (precomputed > 0) {
                        balance = precomputed;
                        console.log(`💰 itemBalances: Item ${itemIdStr} using precomputed balanceHours = ${balance}`);
                    } else {
                        // Fallback: Round balance to avoid floating-point errors and compute split
                        balance = Math.round(balance);
                        const installmentNo = Number(selectedForItem.installmentNo);
                        const base = Math.floor(balance / multiPayment);
                        const remainder = balance - base * multiPayment;
                        balance = installmentNo === 1 ? base + remainder : base;
                        console.log(`💰 itemBalances: Item ${itemIdStr} computed fallback installment ${installmentNo}, balance = ${balance}`);
                    }
                } else {
                    console.log(`💰 itemBalances: Item ${itemIdStr} original selected, balance = ${balance}`);
                }

                balances[itemIdStr] = balance;
            });

            console.log('💳 itemBalances computed:', balances);
            return balances;
        });

        // isExist: treat same offer + same selected type as existing (so user can add both price-variants separately)



        // ✅ Check if offer already exists in cart
     const isExist = (offer: OfferType & { selected_price_type?: 'final' | 'second'; override_price?: boolean; final_price?: number; service_label?: string }) => {
    // override_price items (custom services) are always considered unique
    if (offer.override_price) {
        // however we still want to detect exact duplicate overrides to avoid duplicate clicks
        return data.value.find((item: any) =>
            item.override_price &&
            item.offer_id === offer.id &&
            item.final_price === offer.final_price &&
            (item.service_label || '') === (offer.service_label || '')
        );
    }

    return data.value.find(
        (item: any) =>
            (item.offer_id === offer.id || item.id === offer.id) &&
            (item.selected_price_type || 'final') === (offer.selected_price_type || 'final')
    );
};


        const getTranche = () => {
            const res = data.value.find(
                (item: any) => item.offer?.multi_payment > 1 || item.multi_payment > 1
            );
            return res?.offer?.multi_payment || res?.multi_payment;
        };

        const getAll = () => {
            query.fetch().finally(() => {
                state.loading = {};
            });
        };




        // ✅ Add offer
const add = (offer: OfferType, callback = () => {}, selectedPriceType?: 'final' | 'second') => {
    const pricing = getAgencyPricing(offer);

    // If this offer was passed with an explicit override (e.g. service from description),
    // prefer the incoming final_price. Otherwise derive from agency pricing.
    const final_price_from_agency = Number(
        pricing?.original_price ??
        pricing?.price_ht ??
        0
    );

    // If no final price available at all, log and abort
    if (!final_price_from_agency && !offer.final_price) {
        console.error('Agency price missing and no override provided', offer);
        return;
    }

    // Only set agency-derived values when there is no explicit override
    if (!offer.override_price) {
        offer.final_price = Number(offer.final_price ?? final_price_from_agency);
        offer.balance = Number(pricing?.balance ?? 0);
        // If the agency pricing didn't supply a balance, try to infer from name
        if (!offer.balance) {
            const inferred = parseHoursFromTitle(offer.name ?? offer.service_label ?? '');
            if (inferred !== null) {
                offer.balance = inferred;
                console.log('💡 add(): inferred balance from title', offer.balance);
            }
        }
        offer.multi_payment = Number(pricing?.multi_payment ?? 1);
        offer.selected_price_type = selectedPriceType || 'original';
    } else {
        // mark as custom/guest-selected service
        offer.final_price = Number(offer.final_price ?? final_price_from_agency);
        offer.balance = Number(offer.balance ?? 0);
        // also infer if override gave 0 but the name has hours
        if (!offer.balance) {
            const inferred2 = parseHoursFromTitle(offer.name ?? offer.service_label ?? '');
            if (inferred2 !== null) {
                offer.balance = inferred2;
                console.log('💡 add(): inferred balance from title on override', offer.balance);
            }
        }
        offer.multi_payment = Number(offer.multi_payment ?? 1);
        offer.selected_price_type = selectedPriceType || 'final';
    }

    // 🔥 save agency_pricing array (parsed)
    if (typeof offer.agency_pricing === 'string') {
        try {
            offer.agency_pricing = JSON.parse(offer.agency_pricing);
        } catch {
            offer.agency_pricing = [];
        }
    }

    // ------------------------------------------------------------------
    // menu-card fallback: if we still have a zero price after normal
    // agency/original logic try resolving with our helper which can parse
    // the `caracteristiques` blob.  If a value is found we treat it as an
    // "override" so that it persists to sessionStorage and is sent to the
    // backend when the item is added.
    if ((!offer.override_price || !offer.final_price) && offer.final_price <= 0) {
        const fb = resolveOfferPrice(offer, null);
        if (fb && fb > 0) {
            console.log('💾 add(): applying fallback price for menu offer', fb, offer.id);
            offer.final_price = fb;
            // mark override so writeOverrideToSession captures it
            offer.override_price = true;
        }
    }

    const writeOverrideToSession = (cartItemKey?: string) => {
        if (!offer.override_price) return;
        try {
            const map = JSON.parse(sessionStorage.getItem('cart_price_overrides') || '{}');
            const key = String(cartItemKey ?? offer.id);
            // Only use service_label or name, NEVER caracteristiques (which may contain HTML)
            const entry: any = { price: Number(offer.final_price), label: String(offer.service_label ?? offer.name ?? '') };
            // Always store hours (even 0) so we know an override was applied
            const hrs = Number(offer.balance ?? 0);
            if (!isNaN(hrs)) entry.hours = hrs;
            map[key] = entry;
            sessionStorage.setItem('cart_price_overrides', JSON.stringify(map));
        } catch (e) {
            console.error('Failed to write cart override to sessionStorage', e);
        }
    };

    if (loggedUser.value) {
        // build payload fields; always include balance if present, price only when overridden
        const payloadItem: any = {
            offer_id: offer.id,
            quantity: 1,
            tranches: offer.multi_payment,
            selected_price_type: offer.selected_price_type || 'final',
        };
        if (offer.override_price) {
            payloadItem.price = Number(offer.final_price ?? 0);
        }
        if (offer.balance !== undefined && offer.balance !== null) {
            payloadItem.balance = Number(offer.balance);
        }

        mutation.mutate(route(api.storeOrUpdateMany), 'post', {
            data: [payloadItem],
        }).then(() => {
            // After server add, fetch latest cart and attempt to locate created row
            query.fetch().then(() => {
                try {
                    const overridesMap = JSON.parse(sessionStorage.getItem('cart_price_overrides') || '{}');
                    // Find a cart row for this offer that doesn't already have an override
                    const created = (query.data || []).find((row: any) => String(row.offer?.id) === String(offer.id) && !overridesMap[String(row.id)]);
                    if (created) {
                        writeOverrideToSession(String(created.id));
                    } else {
                        // Fallback: first matching row
                        const fallback = (query.data || []).find((row: any) => String(row.offer?.id) === String(offer.id));
                        if (fallback) writeOverrideToSession(String(fallback.id));
                    }
                } catch (e) {
                    // ignore override write error
                }
                getAll();
                callback?.();
            }).catch(() => {
                getAll();
                callback?.();
            });
        });
    } else {
        // For guests we persist the full offer locally (including final_price override)
        const localId = `local_${Date.now()}_${Math.random().toString(36).slice(2,8)}`;
        // Store as a cart-row-like object so item.id is available
        setLocal([...(local.value || []), { id: localId, offer }]);
        // Also persist the override mapping for consistency (keyed by cart item id)
        writeOverrideToSession(localId);
    }
};




        const addMany = async (payload: Partial<OfferType>[]) => {
            if (loggedUser.value) {
                try {
                    const _payload = payload.map((o) => {
                        const p: any = {
                            offer_id: o.id,
                            quantity: 1,
                            tranches: o.multi_payment || 1,
                            selected_price_type: o.selected_price_type || 'final',
                        };
                        if (o.override_price || o.selected_price_type === 'installment') {
                            p.price = Number(o.final_price ?? 0);
                        }
                        if (o.balance !== undefined && o.balance !== null) p.balance = Number(o.balance);
                        return p;
                    });
                    const res = await mutation.mutate(route(api.storeOrUpdateMany, uid), 'post', {
                        data: _payload,
                    });
                    getAll();
                    return res;
                } catch (error) {
                    return error;
                }
            }
        };

        // ✅ Remove
        const remove = (offer: OfferType & { selected_price_type?: 'final' | 'second' }) => {
            if (loggedUser.value) {
                state.successMsg = '';
                state.loading[offer.id] = true;
                const el = isExist(offer);
                if (el) {
                    mutation.mutate(route(api.delete, el.id), 'delete').then(() => {
                        state.successMsg = 'Produit est bien retiré du panier';
                        getAll();
                    });
                }
            } else {
                const data = [...(local.value || [])].filter(
                    (item) =>
                        !(
                            item.id === offer.id &&
                            (item.selected_price_type || 'final') ===
                                (offer.selected_price_type || 'final')
                        )
                );
                setLocal(data);
            }
        };

        const removeAll = () => {
            local.value = [];
            setLocal(local.value);
        };

        const reset = () => {
            query.reset();
            state.successMsg = '';
            removeAll();
            getAll();
        };

        const syncCart = () => {
            if (!local.value?.length) return;
            const payload = local.value.map((item) => ({
                offer_id: item.id,
                quantity: 1,
                tranches: 1,
                selected_price_type: item.selected_price_type || 'final',
            }));
            mutation.mutate(route(api.storeOrUpdateMany), 'post', payload).then(() => {
                setLocal([]);
                state.authModal = false;
                location.reload();
            });
        };

        onMounted(() => {
            if (loggedUser.value) {
                getAll();
            }
        });

        // 🆕 Helper: Set selected installment for an item
        const setSelectedInstallment = (itemId: string | number, installmentNo: number | null, priceType: 'original' | 'installment' = 'original') => {
            if (!itemId) return;

            // Find the cart item to get its offer_id as well
            const cartItem = data.value.find((item: any) => String(item.id) === String(itemId));
            const offerId = cartItem?.offer_id || cartItem?.offer?.id;
            const offerObj = cartItem?.offer;

            // Compute the balance for this selection
            let computedBalance = 0;
            if (offerObj) {
                let balance = Number(offerObj.balance ?? 0);

                // Check for frontend override
                try {
                    const overrides = JSON.parse(sessionStorage.getItem('cart_price_overrides') || '{}');
                    const itemKey = String(itemId);
                    const offerKey = String(offerId);
                    const override = (itemKey && overrides[itemKey]) || overrides[offerKey];
                    if (override && override.hours !== undefined && override.hours !== null) {
                        balance = Number(override.hours) || 0;
                    }
                } catch (e) {
                    // ignore
                }

                // If INSTALLMENT selection, divide balance by count
                if (priceType === 'installment') {
                    let pricing = offerObj.agency_pricing;
                    if (typeof pricing === 'string') {
                        try { pricing = JSON.parse(pricing); } catch { pricing = null; }
                    }
                    const installments = Array.isArray(pricing) && pricing.length
                        ? (pricing[0]?.installments || [])
                        : (offerObj.installments || []);
                    const count = installments.length || Number(offerObj.multi_payment || 1);

                    if (count > 1) {
                        computedBalance = Math.floor(balance / count);
                        // Add remainder to first installment
                        if (installmentNo === 1 || installmentNo === (installments[0]?.no || 1)) {
                            computedBalance += (balance - (computedBalance * count));
                        }
                    } else {
                        computedBalance = balance;
                    }
                } else {
                    // ORIGINAL selection: use full balance
                    computedBalance = balance;
                }
            }

            const selection = {
                installmentNo: installmentNo || undefined,
                priceType,
                balanceHours: computedBalance  // ✅ NEW: store computed balance directly
            };

            // Store by both item.id and offer_id as strings for flexible lookup
            const itemIdStr = String(itemId);
            const offerIdStr = String(offerId);

            selectedInstallments[itemIdStr] = selection;
            if (offerId) {
                selectedInstallments[offerIdStr] = selection;
            }

            // Persist selected installments WITH computed balance to sessionStorage
            try {
                const plain = JSON.parse(JSON.stringify(selectedInstallments || {}));
                sessionStorage.setItem('cart_selected_installments', JSON.stringify(plain));
                console.log(`✅ setSelectedInstallment: item ${itemIdStr}, type ${priceType}, balanceHours ${computedBalance}`);
            } catch (e) {
                // ignore
            }

            // Persist selection server-side for logged users so backend/sales can reference it
            if (loggedUser.value && offerId) {
                const payload: any = {
                    tranches: offerObj?.multi_payment || offerObj?.tranches || 1,
                    selected_price_type: priceType === 'installment' ? 'installment' : 'original',
                };
                if (installmentNo) payload.selected_installment_no = installmentNo;
                // include computed balance so backend can persist installment split
                payload.balance = Number(computedBalance ?? 0);

                // Use API route to update cart detail for this offer
                mutation.mutate(route(api.update, offerId), 'patch', payload)
                    .then(() => {
                        // Refresh cart data to reflect server state
                        getAll();
                    })
                    .catch(() => {
                        // Ignore errors but refresh to stay consistent
                        getAll();
                    });
            }

            // Persist a lightweight preview of this cart item (id + offer) so dashboard can compute balances
            try {
                const previews = JSON.parse(sessionStorage.getItem('cart_preview_items') || '{}');
                previews[itemIdStr] = {
                    id: itemIdStr,
                    offer: cartItem?.offer ? JSON.parse(JSON.stringify(cartItem.offer)) : null,
                };
                sessionStorage.setItem('cart_preview_items', JSON.stringify(previews));
            } catch (e) {
                // ignore
            }
            console.log(`✅ setSelectedInstallment: item ${itemIdStr}, installment ${installmentNo}, type ${priceType}`, selectedInstallments);
        };

        return {
            getAll,
            isExist,
            add,
            addMany,
            remove,
            removeAll,
            syncCart,
            getTranche,
            reset,
            state,
            count,
            data,
            mutation,
            query,
            loggedUser,
            prices,
            itemPrices,
            itemBalances,
            resolveOfferPrice,
            setSelectedInstallment,
            selectedInstallments,

        };
    })();
