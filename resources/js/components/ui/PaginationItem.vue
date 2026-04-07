<script setup lang="ts">
defineProps<{
  class?: string
}>()
</script>

<template>
  <li :class="$props.class">
    <slot />
  </li>
</template>