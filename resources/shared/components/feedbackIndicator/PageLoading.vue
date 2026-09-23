<template>
    <main class="animate-pulse flex flex-col gap-7">
        <div class="flex justify-between items-center">
            <div class="block w-full">
                <div class="flex gap-2">
                    <span class="h-4 w-32 rounded-lg max-w-xs bg-slate-300"></span>
                    <span class="h-4 w-32 rounded-lg max-w-xs bg-slate-300"></span>
                </div>
                <div class="h-8 mt-2 w-full rounded-lg max-w-xs bg-slate-300"></div>
            </div>
            <div class="h-9 w-full rounded-full max-w-[10rem] bg-slate-300"></div>
        </div>
        <ul class="flex flex-col p-4 bg-white rounded-xl shadow-md shadow-slate-200 gap-4 mb-5 border">
            <li
                v-for="idx in 10"
                :key="idx"
                class="h-7 w-full rounded-lg bg-slate-300"
                :class="{ 'max-w-lg': idx === 10 }"
            ></li>
        </ul>
    </main>
</template>
