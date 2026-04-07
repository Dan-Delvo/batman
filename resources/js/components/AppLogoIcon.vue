<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 40 42"
        :class="className"
        v-bind="$attrs"
    >
        <g
            fill="none"
            stroke="currentColor"
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="3"
        >
            <path d="M20 3v5" />
            <path d="M14 8h12" />
            <rect x="6" y="10" width="28" height="22" rx="5" />
            <path d="M6 18H2v8h4" />
            <path d="M34 18h4v8h-4" />
            <path d="M13 32v6" />
            <path d="M27 32v6" />
            <path d="M12 26h16" />
        </g>
        <circle cx="14" cy="20" r="2.2" fill="currentColor" />
        <circle cx="26" cy="20" r="2.2" fill="currentColor" />
    </svg>
</template>
