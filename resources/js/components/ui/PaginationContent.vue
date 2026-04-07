<script setup lang="ts">
import { cn } from '@/lib/utils'

defineProps<{
  class?: string
}>()
</script>

<template>
  <ul :class="cn('flex flex-row items-center gap-2', $props.class)">
    <slot />
  </ul>
</template>