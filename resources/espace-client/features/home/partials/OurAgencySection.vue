<template>
    <div class="bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100 py-24 sm:py-32 relative isolate overflow-hidden">
        <!-- Animated background elements -->
        <div class="absolute inset-0 overflow-hidden">
            <div
                class="absolute -top-40 -right-40 w-80 h-80 bg-gradient-to-br from-blue-400/20 to-purple-500/20 rounded-full blur-3xl animate-pulse"
            ></div>
            <div
                class="absolute -bottom-40 -left-40 w-80 h-80 bg-gradient-to-tr from-cyan-400/20 to-blue-500/20 rounded-full blur-3xl animate-pulse delay-1000"
            ></div>
            <div
                class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-96 h-96 bg-gradient-to-r from-indigo-400/10 to-purple-500/10 rounded-full blur-3xl animate-pulse delay-500"
            ></div>
        </div>

        <div class="mx-auto max-w-2xl px-6 lg:max-w-7xl lg:px-8 relative z-10">
            <!-- Header section with call-to-action -->
            <div class="text-center mb-16">
                <div class="max-w-2xl mx-auto">
                    <div class="relative inline-block mb-8">
                        <img
                            src="/assets/clients/nos-agences-rdv.webp"
                            alt=""
                            class="object-contain mx-auto max-w-sm w-full transform hover:scale-105 transition-transform duration-300"
                        />
                        <div class="absolute -inset-4 bg-gradient-to-r from-blue-500/20 to-purple-500/20 rounded-full blur-xl -z-10"></div>
                    </div>
                    <p class="text-xl text-gray-700 font-medium">
                        Nos équipes sont là pour t'accompagner en toute sérénité !
                        <Link
                            :href="route(routes.agencies)"
                            class="inline-flex items-center ml-2 text-blue-600 hover:text-blue-700 font-semibold group transition-colors duration-200"
                        >
                            Voir nos agences
                            <span class="ml-1 transform group-hover:translate-x-1 transition-transform duration-200">→</span>
                        </Link>
                    </p>
                </div>
            </div>

            <!-- Two agency cards in a modern playful layout -->
            <div class="grid gap-8 lg:grid-cols-2 lg:gap-12">
                <!-- Agence Creil -->
                <div v-if="shouldShowCreil" class="group relative">
                    <!-- Floating decorative elements -->
                    <div
                        class="absolute -top-4 -right-4 w-8 h-8 bg-gradient-to-br from-blue-400 to-purple-500 rounded-full opacity-60 group-hover:opacity-100 transition-opacity duration-300 animate-bounce"
                    ></div>
                    <div
                        class="absolute -bottom-4 -left-4 w-6 h-6 bg-gradient-to-tr from-cyan-400 to-blue-500 rounded-full opacity-60 group-hover:opacity-100 transition-opacity duration-300 animate-bounce delay-500"
                    ></div>

                    <!-- Main card -->
                    <div
                        class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-white via-blue-50 to-indigo-50 shadow-2xl hover:shadow-3xl transition-all duration-500 transform hover:-translate-y-2 border border-white/20"
                    >
                        <!-- Gradient overlay -->
                        <div class="absolute inset-0 bg-gradient-to-br from-blue-500/5 via-transparent to-purple-500/5"></div>

                        <!-- Content -->
                        <div class="relative p-8">
                            <!-- Header with icon -->
                            <div class="text-center mb-6">
                                <div
                                    class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-blue-500 to-purple-600 rounded-2xl mb-4 shadow-lg"
                                >
                                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                                        ></path>
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
                                        ></path>
                                    </svg>
                                </div>

                                <h3 class="text-3xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                                    <Link :href="route('offers.index', { is_auto: 0, agency: 'creil' })">Agence Creil</Link>
                                </h3>

                                <p class="mt-3 text-gray-600 font-medium">
                                    15 rue des Pierres 60100 creil<br />
                                    <span class="text-blue-600 font-semibold">du mercredi au samedi de 9h à 18h</span>
                                </p>
                            </div>

                            <!-- Image with floating effect -->
                            <div class="relative">
                                <div class="absolute inset-0 bg-gradient-to-t from-blue-500/20 to-transparent rounded-2xl"></div>
                                <img
                                    src="/assets/clients/gare-creil.webp"
                                    alt="Agence Creil"
                                    class="w-full h-64 object-cover rounded-2xl shadow-xl transform group-hover:scale-105 transition-transform duration-500"
                                />
                                <!-- Floating badge -->
                                <div
                                    class="absolute top-4 right-4 bg-white/90 backdrop-blur-sm px-3 py-1 rounded-full text-sm font-semibold text-blue-600 shadow-lg"
                                >
                                    🚗 Disponible
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Agence Toulouse -->
                <div v-if="shouldShowToulouse" class="group relative">
                    <!-- Floating decorative elements -->
                    <div
                        class="absolute -top-4 -left-4 w-8 h-8 bg-gradient-to-br from-purple-400 to-pink-500 rounded-full opacity-60 group-hover:opacity-100 transition-opacity duration-300 animate-bounce delay-300"
                    ></div>
                    <div
                        class="absolute -bottom-4 -right-4 w-6 h-6 bg-gradient-to-tr from-pink-400 to-purple-500 rounded-full opacity-60 group-hover:opacity-100 transition-opacity duration-300 animate-bounce delay-700"
                    ></div>

                    <!-- Main card -->
                    <div
                        class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-white via-purple-50 to-pink-50 shadow-2xl hover:shadow-3xl transition-all duration-500 transform hover:-translate-y-2 border border-white/20"
                    >
                        <!-- Gradient overlay -->
                        <div class="absolute inset-0 bg-gradient-to-br from-purple-500/5 via-transparent to-pink-500/5"></div>

                        <!-- Content -->
                        <div class="relative p-8">
                            <!-- Header with icon -->
                            <div class="text-center mb-6">
                                <div
                                    class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-purple-500 to-pink-600 rounded-2xl mb-4 shadow-lg"
                                >
                                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                                        ></path>
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
                                        ></path>
                                    </svg>
                                </div>
                                <h3 class="text-3xl font-bold bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent">
                                    <Link :href="route('offers.index', { is_auto: 1, agency: 'toulouse' })">Agence Toulouse</Link>
                                </h3>

                                <p class="mt-3 text-gray-600 font-medium">
                                    139 boulevard Déodat de Séverac 31300<br />
                                    Boulevard André Netwiller, 31200 Toulouse <br>
                                    <span class="text-purple-600 font-semibold">du lundi au vendredi de 16h à 19h</span>
                                </p>
                            </div>

                            <!-- Image with floating effect -->
                            <div class="relative">
                                <div class="absolute inset-0 bg-gradient-to-t from-purple-500/20 to-transparent rounded-2xl"></div>
                                <img
                                    src="/assets/clients/toulouse.webp"
                                    alt="Agence Toulouse"
                                    class="w-full h-64 object-cover rounded-2xl shadow-xl transform group-hover:scale-105 transition-transform duration-500"
                                />
                                <!-- Floating badge -->
                                <div
                                    class="absolute top-4 right-4 bg-white/90 backdrop-blur-sm px-3 py-1 rounded-full text-sm font-semibold text-purple-600 shadow-lg"
                                >
                                    🚗 Disponible
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ClientButton } from '@espace-client/components';
import { routes } from '@espace-client/routes';
import { Link } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const user = page.props.auth?.user;
const studentCity = user?.ville?.toLowerCase();

// Check if user is a student (has ville field)
const isStudent = computed(() => {
    // If user has a 'ville' field, they are a student
    return !!user?.ville;
});

// Show Creil agency if:
// - Secretaries see both agencies
// - If no user, show both
// - If user is not a student (admin/staff), show both
// - If user is a student, only show if their ville is 'creil'
const shouldShowCreil = computed(() => {
    if (user?.is_secretary) return true;
    if (!user) return true;
    if (!isStudent.value) return true;
    return studentCity === 'creil';
});

// Show Toulouse agency if:
// - Secretaries see both agencies
// - If no user, show both
// - If user is not a student (admin/staff), show both
// - If user is a student, only show if their ville is 'toulouse'
const shouldShowToulouse = computed(() => {
    if (user?.is_secretary) return true;
    if (!user) return true;
    if (!isStudent.value) return true;
    return studentCity === 'toulouse';
});

// Debug logs
console.log('User:', user);
console.log('Student ville:', studentCity);
console.log('Is student?', isStudent.value);
console.log('Show Creil?', shouldShowCreil.value);
console.log('Show Toulouse?', shouldShowToulouse.value);
</script>

<style scoped>
@keyframes float {
    0%,
    100% {
        transform: translateY(0px);
    }
    50% {
        transform: translateY(-10px);
    }
}

.animate-float {
    animation: float 3s ease-in-out infinite;
}

.shadow-3xl {
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
}
</style>
