<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Card, Button, InputField } from '@shared/components';
import { onMounted, ref, nextTick, computed } from 'vue';
import { loadStripe } from '@stripe/stripe-js';
import axios from 'axios';
import { useAlert, useCart } from '@shared/stores';
import { moneyFormat } from '@shared/utils';
import { routes } from '@espace-client/routes';


const tempKey = 'pk_test_51RMTba02XduUdvgxJ0MxXHL2sCGb4LerePgjFf00XIIIWLaXjBXSWLiEPJ0vigyC4HMKgC6FFSC3erb6wUYj8C2C00nBqqa1zm';
const stripeKey = ref(import.meta.env.VITE_STRIP_PUBLISHABLE || tempKey);
const paymentError = ref<string>(''); // for showing error to customer

const cart = useCart();
const emit = defineEmits(['success']);

const stripe = ref<any>(null);
const elements = ref<any>(null);
const stripeLoaded = ref(false);
const disabled = ref(true);
const loading = ref(false);
const alerts = useAlert();

const cardInfo = ref({
    brand: '',
    errors: {
        isError: false,
        message: '',
    },
});

const form = useForm({
    email: '',
    name: '',
    cardNumber: '',
    cardExpiry: '',
    cardCvc: '',
    country: 'FR',
    postalCode: '',
});

// Track mounted state
const elementsMounted = ref({
    cardNumber: false,
    cardExpiry: false,
    cardCvc: false,
});

// ✅ Use computed total from cart store (which already accounts for agency pricing,
// overrides and selected installments).  Simplifies logic and keeps pricing
// consistent with the checkout summary and the paypal component.
const subtotal = computed(() => cart.prices.total || 0);

onMounted(async () => {
    try {
        stripe.value = await loadStripe(stripeKey.value);

        if (stripe.value) {
            elements.value = stripe.value.elements();
            stripeLoaded.value = true;

            // Wait for next tick to ensure DOM is ready
            await nextTick(() => {
                const style = {
                    base: {
                        iconColor: '#666EE8',
                        color: '#31325F',
                        fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
                        fontSize: '14px',
                        '::placeholder': {
                            color: '#CFD7E0',
                        },
                    },
                    invalid: {
                        color: '#E25950',
                    },
                };
                setTimeout(() => {
                    if (document.getElementById('card-number')) {
                        const cardNumber = elements.value.create('cardNumber', {
                            showIcon: true,
                            style,
                        });
                        cardNumber.mount('#card-number');
                        cardNumber.on('change', (event: any) => handleElementChange('cardNumber', event));
                        elementsMounted.value.cardNumber = true;
                    }

                    if (document.getElementById('card-expiry')) {
                        const cardExpiry = elements.value.create('cardExpiry', {
                            placeholder: 'MM/AA',
                            style,
                        });
                        cardExpiry.mount('#card-expiry');
                        cardExpiry.on('change', (event: any) => handleElementChange('cardExpiry', event));
                        elementsMounted.value.cardExpiry = true;
                    }

                    if (document.getElementById('card-cvc')) {
                        const cardCvc = elements.value.create('cardCvc', {
                            placeholder: 'CVC',
                            style,
                        });
                        cardCvc.mount('#card-cvc');
                        cardCvc.on('change', (event: any) => handleElementChange('cardCvc', event));
                        elementsMounted.value.cardCvc = true;
                    }
                }, 100);
            });
        }
    } catch (error) {
        console.error('Error loading Stripe:', error);
        alerts.show({
            type: 'error',
            title: 'Erreur de chargement du système de paiement',
        });
    }
});

const elementsComplete = {
    cardNumber: false,

    cardExpiry: false,
    cardCvc: false,
};

function handleElementChange(elementType: keyof typeof elementsComplete, event: any) {
    elementsComplete[elementType] = event.complete;
    disabled.value = !(elementsComplete.cardNumber && elementsComplete.cardExpiry && elementsComplete.cardCvc);

    if (event.error) {
        cardInfo.value.errors.isError = true;
        if (event.error.code.includes('number')) {
            cardInfo.value.errors.message = "Votre numéro de carte n'est pas valide";
        } else if (event.error.code.includes('year') || event.error.code.includes('month')) {
            cardInfo.value.errors.message = 'La date de votre carte est expirée ou invalide';
        } else if (event.error.code.includes('cvc')) {
            cardInfo.value.errors.message = 'Le code CVC est invalide';
        }
    } else {
        cardInfo.value.errors.isError = false;
        if (elementType === 'cardNumber' && event.brand) {
            cardInfo.value.brand = event.brand;
        }
    }
}

const onPay = async () => {
    if (!cart.loggedUser) {
        cart.state.authModal = true;
        return;
    }

    loading.value = true;
    disabled.value = true;

    try {
        // Get the card element directly from Stripe
        const cardElement = elements.value.getElement('cardNumber');

        // Create payment intent first
        // build installments payload if user selected them
        const installments: any[] = [];
        if (cart.data && cart.data.length) {
            for (const item of cart.data) {
                const offerId = item.offer?.id || item.offer_id || item.offerId;
                const itemKey = String(item.id || '');
                const sel = cart.selectedInstallments[itemKey] || cart.selectedInstallments[String(offerId)];
                if (sel && sel.installmentNo) {
                    installments.push({ offer_id: offerId, installment_no: sel.installmentNo });
                }
            }
        }

        const payload: any = {
            amount: subtotal.value,
            balance: cart.prices.balance,
        };
        if (installments.length) {
            payload.installments = installments;
        }

        const { data: paymentIntentData } = await axios.post(route(routes.api.payment.strip.store), payload);

        if (!paymentIntentData.clientSecret) {
            throw new Error('No client secret received');
        }

        // the backend now returns multiple sales in `sales` array; older clients expect a single
        // `sale` object.  normalize to a single record so subsequent code can always reference
        // `primarySale.id`.
        const primarySale = paymentIntentData.sale ??
            (Array.isArray(paymentIntentData.sales) && paymentIntentData.sales.length
                ? paymentIntentData.sales[0]
                : null);
        if (!primarySale || !primarySale.id) {
            throw new Error('Missing sale information from payment response');
        }

        // Confirm the payment with 3D Secure
        const { error: confirmError, paymentIntent } = await stripe.value.confirmCardPayment(paymentIntentData.clientSecret, {
            payment_method: {
                card: cardElement,
                billing_details: {
                    name: form.name,
                    address: {
                        country: form.country,
                        // postal_code: 'postalCode',
                    },
                },
            },
        });

        if (confirmError) {
            throw confirmError;
        }

        if (paymentIntent.status === 'succeeded') {
            // Call the success endpoint to process the payment
            const { data: successData } = await axios.post(route(routes.api.payment.strip.success), {
                paymentIntentId: paymentIntent.id,
                saleId: primarySale.id,
            });

            if (successData.success) {
                cart.state.sale = successData.sale;
                if (successData.sales) {
                    (cart.state as any).sales = successData.sales;
                }
                cart.reset();
                form.reset();
                emit('success');
            }
        } else if (paymentIntent.status === 'requires_action') {
            // Handle additional authentication steps if needed
            const { error: actionError } = await stripe.value.handleCardAction(paymentIntentData.clientSecret);

            if (actionError) {
                throw actionError;
            }

            // Payment succeeded after additional authentication
            const { data: successData } = await axios.post(route(routes.api.payment.strip.success), {
                paymentIntentId: paymentIntent.id,
                saleId: primarySale.id,
            });

            if (successData.success) {
                cart.state.sale = successData.sale;
                if (successData.sales) {
                    (cart.state as any).sales = successData.sales;
                }
                cart.reset();
                form.reset();
                emit('success');
            }
        }
    } catch (err: any) {
        console.error('Stripe Payment Error:', err);

        let errorMessage = '';

        if (err?.response?.data?.error?.message) {
            errorMessage = err.response.data.error.message;
        } else if (err?.message) {
            errorMessage = err.message;
        } else if (err?.error?.message) {
            errorMessage = err.error.message;
        } else {
            errorMessage = 'Payment failed. Please try again.';
        }

        // show in alert (if your system works)
        alerts.show({
            type: 'error',
            title: errorMessage,
        });

        // also update reactive state so UI shows it
        paymentError.value = errorMessage;
    } finally {
        loading.value = false;
        disabled.value = false;
    }
};
</script>

<template>
    <Card class="gap-2 flex-1 flex flex-col rounded-3xl mb-3" title="Paiement informations">
        <InputField id="name" v-model="form.name" label="Nom du titulaire de la carte" :error="form.errors.name" />

        <div v-if="stripeLoaded" class="space-y-3 bg-slate-50 -mx-3 px-3 rounded-lg py-3">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Numéro de carte</label>
                <div id="card-number" class="p-3 border border-gray-300 rounded-md"></div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date d'expiration</label>
                    <div id="card-expiry" class="p-3 border border-gray-300 rounded-md"></div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Code CVC</label>
                    <div id="card-cvc" class="p-3 border border-gray-300 rounded-md"></div>
                </div>
            </div>
        </div>
        <div v-else class="text-center py-4">Chargement du système de paiement...</div>

        <span v-if="cardInfo.errors.isError" class="text-red-600 text-xs">
            {{ cardInfo.errors.message }}
        </span>

        <span v-if="paymentError" class="text-red-600 text-sm mt-2">
            {{ paymentError }}
        </span>

        <section class="flex flex-col gap-3 pt-2">
            <slot />
            <Button class="h-12" :disabled="disabled || !stripeLoaded" :loading="loading" variant="primary" full @click="onPay">
                <slot name="btn"> Confirmer et Payer ({{ moneyFormat(subtotal) }}) </slot>
                <div class="flex gap-2">
                    <img src="/assets/icons/payment-methods/maestro.svg" alt="maestro icon" class="w-8" />
                    <img src="/assets/icons/payment-methods/masterCard.svg" salt="masterCard icon" class="w-8" />
                    <img src="/assets/icons/payment-methods/visa.svg" alt="visa icon" class="w-8" />
                </div>
            </Button>
        </section>
    </Card>
</template>

<style>
.StripeElement {
    width: 100%;
    height: 42px;
    padding: 12px;
}

.StripeElement--cardNumber {
    padding-right: 40px;
}

.StripeElement--invalid {
    border-color: #e25950;
}
</style>
