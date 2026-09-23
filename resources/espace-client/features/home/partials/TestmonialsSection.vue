<script setup>
import { onMounted } from 'vue';
import reviews from '@assets/json/reviews.json';
import LoopingSlider from './LoopingSlider.vue';
import ReviewCard from './ReviewCard.vue';
import { useDimensions } from '@shared/hooks';
const { md } = useDimensions();
onMounted(() => {
    // getReviews();
});
const featuredTestimonial = {
    body: 'J’ai enfin obtenu mon permis grâce à Pass Permis Facile à Toulouse, et je les recommande les yeux fermés ! Après avoir raté deux fois mon permis dans une autre auto-école, je suis arrivée ici un peu démotivée, mais tout a changé grâce à leur incroyable équipe. Les moniteurs sont bienveillants, professionnels et patients : jamais je ne me suis fait crier dessus ou juger, ce qui m’a vraiment redonné confiance en moi. Un immense merci à Yasmine et Dominique pour leur pédagogie et leur soutien tout au long de mon apprentissage, et un grand merci à Linda pour sa gentillesse et pour m’avoir trouvé une date rapide. Merci à toute l’équipe !',
    author: {
        name: 'Zahide Oztepe',
        handle: 'zahideoztepe',
        imageUrl: 'https://lh3.googleusercontent.com/a/ACg8ocL1NaYg1c9eKvw3RF5vOEmy3RA7sHBYpGwx_oqGeQ240ADtMA=s120-c-rp-mo-br100',
    },
};
function splitArray(arr) {
    const total = arr.length;
    const first30 = Math.ceil(total * 0.3);
    const second30 = first30;
    const remaining40 = total - first30 - second30;

    return [arr.slice(0, first30), arr.slice(first30, first30 + second30), arr.slice(first30 + second30)];
}
const [reviews1, reviews2, reviews3] = splitArray(reviews);
</script>
<template>
    <div class="relative isolate pb-32 pt-24 sm:pt-32 bg-white">
        <div class="mx-auto max-w-7xl md:px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <img src="/assets/clients/nos-eleves-1.webp" alt="" class="mx-auto max-w-xs w-full" />
                <p class="mt-2 text-balance text-2xl font-medium tracking-tight text-dark2 sm:text-3xl">
                    Nous avons travaillé avec des milliers d'étudiants incroyables
                </p>
            </div>
            <ul
                class="mt-16 md:-mx-4 grid px-4 grid-cols-1 grid-rows-1 md:gap-8 text-sm/6 text-gray-900 sm:mt-20 md:grid-cols-4 overflow-hidden relative"
            >
                <li class="absolute max-md:hidden inset-x-0 top-0 h-28 bg-gradient-to-b from-white via-white/85 to-transparent z-1"></li>
                <li class="absolute max-md:hidden inset-x-0 bottom-0 h-28 bg-gradient-to-t from-white via-white/85 to-transparent z-1"></li>
                <li class="clippath"></li>

                <li><LoopingSlider :reviews="reviews1" :direction="md ? 'vertical' : 'horizontal'" /></li>
                <li class="col-span-2">
                    <LoopingSlider :reviews="reviews3" :reverse="true" :direction="md ? 'vertical' : 'horizontal'" v-slot="{ items }">
                        <div class="flex md:grid md:grid-cols-2 gap-4">
                            <ReviewCard v-for="(item, index) in items" :key="index" class="nth3" :review="item" />
                        </div>
                    </LoopingSlider>
                </li>
                <li>
                    <LoopingSlider :reviews="reviews2" :direction="md ? 'vertical' : 'horizontal'" />
                </li>
            </ul>
        </div>
    </div>
</template>
<style>
.nth3:nth-child(3n) {
    @apply col-span-2 font-bold text-base;
}
</style>
