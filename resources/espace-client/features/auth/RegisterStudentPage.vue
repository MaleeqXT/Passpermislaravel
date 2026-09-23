<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { RegisterStudentform } from './partials';
import { PageContainer } from '@espace-client/components';
// import { ClientLayout } from '@espace-client/layouts';
</script>
<template>
    <PageContainer>
        <div class="flex-center flex-col size-full py-10 px-4">
            <div class="block text-dark text-center pb-10">
                <h2 class="text-4xl font-bold">S'inscrire à votre espace</h2>
                <p class="mt-2 text-sm leading-6 text-gray-500">
                    Tu es déjà membre?
                    {{ ' ' }}
                    <Link href="/login" class="font-semibold text-primary hover:underline"> Se connecter </Link>
                </p>
            </div>
            <div class="w-full max-w-lg block relative h-fit">
                <RegisterStudentform />
            </div>
        </div>
    </PageContainer>
</template>
