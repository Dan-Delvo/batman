<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import Sticker from '@/components/ui/Sticker.vue';
import { Separator } from '@/components/ui/separator';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItem } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const page = usePage();
const companyName = computed(() => page.props.auth?.user?.company || 'Not set');
</script>

<template>
    <header
        class="bg-background/95 flex h-16 shrink-0 items-center gap-2 border-b border-sidebar-border/70 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4"
    >
        <div class="flex h-full w-full items-center justify-between">
            <div class="flex items-center gap-2">
                <SidebarTrigger class="-ml-1" />
                <Breadcrumbs v-if="breadcrumbs?.length" :breadcrumbs="breadcrumbs" />
            </div>

            <div class="flex items-center gap-2">
                <Separator orientation="vertical" class="h-6 w-[3px] bg-foreground" />
                <div class="font-bold hidden md:block">Company:</div>
                <Sticker rotation="none">{{ companyName }}</Sticker>
            </div>
        </div>
    </header>
</template>
