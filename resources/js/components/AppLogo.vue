<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
</script>

<template>
    <div
        class="bg-primary text-primary-foreground flex aspect-square size-8 items-center justify-center rounded-sm border-3 border-foreground shadow-[4px_4px_0px_hsl(var(--shadow-color))]"
    >
        <AppLogoIcon class="size-5 fill-current text-white dark:text-black" />
    </div>
    <div class="ml-2 grid flex-1 text-left text-sm">
        <span class="mb-0.5 truncate leading-tight font-extrabold tracking-wide uppercase"
            >Internship</span
        >
    </div>
</template>
